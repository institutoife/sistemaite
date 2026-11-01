<?php
namespace App\Services;

use App\Models\{User,Persona,CrmSede};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Validator};
use Illuminate\Validation\{Rule,ValidationException};
use Spatie\Permission\Models\Role;

class UserAdministration
{
    public function catalogs(): array { return ['roles'=>Role::where('guard_name','web')->orderBy('name')->get(),'sedes'=>CrmSede::where('activa',true)->orderBy('nombre')->get()]; }
    public function validate(Request $request,?User $user=null): array
    {
        $data=$request->validate([
            'name'=>'required|string|max:64','email'=>['required','email','max:64',Rule::unique('users')->ignore($user?$user->id:null)],
            'persona_id'=>'nullable|integer|exists:personas,id','roles'=>'required|array|min:1','roles.*'=>['required',Rule::exists('roles','name')->where('guard_name','web')],
            'sede_id'=>['nullable','integer',Rule::exists('crm_sedes','id')->where('activa',true)],
            'sedes'=>'nullable|array','sedes.*'=>['integer','distinct',Rule::exists('crm_sedes','id')->where('activa',true)],
            'two_factor_required'=>'sometimes|boolean','reason'=>($user?'required':'nullable').'|string|max:500',
            'foto'=>ProfilePhotoService::RULES,
        ]);
        $family=collect($data['roles'])->diff(config('user_access.family_roles'))->isEmpty();
        $this->assertRoleLinks($data['persona_id']??null,$data['roles']);
        if (!$family&&empty($data['sede_id'])) { throw ValidationException::withMessages(['sede_id'=>'El personal debe tener una Sucursal principal.']); }
        if ($user&&$user->hasRole('Admin')&&auth()->id()===$user->id&&!in_array('Admin',$data['roles'],true)) { throw ValidationException::withMessages(['roles'=>'Solicite a otro administrador la retirada de su propio rol.']); }
        return $data;
    }
    public function assertRoleLinks(?int $personaId,array $roles): void
    {
        if (!array_intersect($roles,['Docente','Padre','Estudiante'])) { return; }
        $person=$personaId?Persona::find($personaId):null;
        if (!$person) { throw ValidationException::withMessages(['persona_id'=>'Vincule la Persona existente para ese rol.']); }
        if (in_array('Docente',$roles,true)&&!$person->docente) { throw ValidationException::withMessages(['persona_id'=>'La Persona seleccionada no tiene ficha de docente.']); }
        if (in_array('Estudiante',$roles,true)&&!$person->estudiante&&!$person->computacion) { throw ValidationException::withMessages(['persona_id'=>'La Persona seleccionada no está registrada como estudiante.']); }
        if (in_array('Padre',$roles,true)&&!$person->hijos()->exists()) { throw ValidationException::withMessages(['persona_id'=>'Vincule primero a esta Persona con sus estudiantes como apoderado.']); }
    }
    private function snapshot(User $user): array
    {
        return ['name'=>$user->name,'email'=>$user->email,'persona_id'=>$user->persona_id,'roles'=>$user->getRoleNames()->sort()->values()->all(),
            'sede_id'=>$user->sede_id,'sedes'=>$user->sedes()->pluck('crm_sedes.id')->sort()->values()->all(),'two_factor_required'=>(bool)$user->two_factor_required];
    }
    public function save(?User $subject,array $data,?string $photo=null): array
    {
        return DB::transaction(function () use ($subject,$data,$photo) {
            $security=app(AccountSecurity::class); $password=null;
            $user=$subject?User::lockForUpdate()->findOrFail($subject->id):new User;
            $before=$subject?$this->snapshot($user):[];
            if ($subject) { $security->protectLastAdmin($user,$data['roles'],$user->access_status,$user->suspended_until?$user->suspended_until->toDateTimeString():null); }
            if (!empty($data['persona_id'])) {
                Persona::withoutGlobalScopes()->where('id',$data['persona_id'])->lockForUpdate()->firstOrFail();
                if (User::where('persona_id',$data['persona_id'])->when($subject,fn($q)=>$q->where('id','!=',$subject->id))->exists()) { throw ValidationException::withMessages(['persona_id'=>'Esta Persona ya tiene usuario. Edite la cuenta existente.']); }
            }
            $user->name=$data['name']; $user->email=$data['email']; $user->persona_id=$data['persona_id']??null; $user->sede_id=$data['sede_id']??null;
            $user->two_factor_required=(bool)($data['two_factor_required']??false);
            if ($photo) { $user->foto=$photo; }
            if (!$subject) { $password=$security->temporaryPassword(); $user->password=Hash::make($password); $user->force_password_change=true; }
            $user->save(); $user->syncRoles($data['roles']);
            $ids=array_unique(array_filter(array_merge($data['sedes']??[],[$user->sede_id]))); $user->sedes()->sync($ids);
            if ($user->persona_id&&!$security->family($user)) {
                foreach ($ids as $id) { DB::table('persona_sede')->insertOrIgnore(['persona_id'=>$user->persona_id,'sede_id'=>$id,'created_at'=>now(),'updated_at'=>now()]); }
            }
            if ($subject) { $security->revoke($user); }
            $user->unsetRelation('roles'); $security->audit($user,$subject?'user_access_updated':'user_created',$before,$this->snapshot($user),$data['reason']??'Alta de cuenta');
            return ['user'=>$user,'password'=>$password];
        });
    }
    public function state(User $subject,array $data): User
    {
        return DB::transaction(function () use ($subject,$data) {
            $user=User::lockForUpdate()->findOrFail($subject->id); $security=app(AccountSecurity::class);
            $security->protectLastAdmin($user,$user->getRoleNames()->all(),$data['status']);
            $before=['status'=>$user->access_status,'suspended_until'=>$user->suspended_until];
            $user->access_status=$data['status']; $user->suspended_until=$data['status']==='suspended'?($data['suspended_until']??null):null;
            $security->revoke($user); $security->audit($user,'user_status_changed',$before,['status'=>$user->access_status,'suspended_until'=>$user->suspended_until],$data['reason']);
            return $user;
        });
    }
    public function schedule(User $subject,array $data): void
    {
        DB::transaction(function () use ($subject,$data) {
            $user=User::lockForUpdate()->findOrFail($subject->id); $security=app(AccountSecurity::class);
            $denials=collect($data['exceptions']??[])->contains(fn($exception)=>!$exception['allowed']&&\Carbon\Carbon::parse($exception['ends_at'],config('user_access.timezone'))->isFuture());
            if (($data['enabled']||$denials)&&$user->hasRole('Admin')&&!$security->unrestrictedAdminOtherThan($user)) { throw ValidationException::withMessages(['enabled'=>'Conserve un administrador activo sin restricción horaria.']); }
            $before=['enabled'=>$user->access_schedule_enabled,'windows'=>DB::table('user_access_windows')->where('user_id',$user->id)->get()->toArray(),'exceptions'=>DB::table('user_access_exceptions')->where('user_id',$user->id)->get()->toArray()];
            DB::table('user_access_windows')->where('user_id',$user->id)->delete(); DB::table('user_access_exceptions')->where('user_id',$user->id)->delete();
            foreach ($data['windows']??[] as $window) { DB::table('user_access_windows')->insert(['user_id'=>$user->id]+$window); }
            foreach ($data['exceptions']??[] as $exception) {
                foreach(['starts_at','ends_at'] as $field){$exception[$field]=\Carbon\Carbon::parse($exception[$field],config('user_access.timezone'))->timezone(config('user_access.timezone'))->format('Y-m-d H:i:s');}
                DB::table('user_access_exceptions')->insert(['user_id'=>$user->id]+$exception);
            }
            $user->access_schedule_enabled=(bool)$data['enabled']; $security->revoke($user);
            $security->audit($user,'schedule_changed',$before,$data,$data['reason']);
        });
    }
}
