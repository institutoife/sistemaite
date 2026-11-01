<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolUsersController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:Listar Roles')->only('index');
        $this->middleware('can:Editar Roles')->only('edit','update','store');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }


    
    public function edit(User $roluser)
    {
        $roles= Role::all();
        return view('users.edit', compact('roluser', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $roluser)
    {
        if (app(\App\Services\AccountSecurity::class)->ready()) {
            $this->authorize('manageAccess',$roluser);
            $data=$request->validate(['roles'=>'required|array|min:1','roles.*'=>['integer','distinct',\Illuminate\Validation\Rule::exists('roles','id')->where('guard_name','web')],'reason'=>'required|string|min:5|max:500']);
            \Illuminate\Support\Facades\DB::transaction(function()use($roluser,$data){
                $user=User::lockForUpdate()->findOrFail($roluser->id);$names=Role::whereIn('id',$data['roles'])->where('guard_name','web')->pluck('name')->all();
                app(\App\Services\UserAdministration::class)->assertRoleLinks($user->persona_id,$names);
                $security=app(\App\Services\AccountSecurity::class);$security->protectLastAdmin($user,$names,$user->access_status,$user->suspended_until?$user->suspended_until->toDateTimeString():null);$before=$user->getRoleNames()->all();
                if(collect($names)->diff(config('user_access.family_roles'))->isNotEmpty()&&!$user->sede_id)throw \Illuminate\Validation\ValidationException::withMessages(['roles'=>'Asigne primero una Sucursal principal desde Editar usuario.']);
                $user->syncRoles($names);$security->revoke($user);$security->audit($user,'roles_changed',['roles'=>$before],['roles'=>$names],$data['reason']);
            });
            return redirect()->route('users.index')->with('success','Roles actualizados; sesiones anteriores revocadas.');
        }
        $roluser->roles()->sync($request->roles);

        return redirect()->route('rolusers.index', $roluser);
    }

    public function store(Request $request){
        $permission = Permission::create(['name' => $request->name]);
        //dd($permission);
    }
    
}
