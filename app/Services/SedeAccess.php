<?php
namespace App\Services;

use App\Models\{User,CrmSede};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class SedeAccess
{
    private $enabled=false,$user,$active,$family=false,$people=[],$contacts=[],$teachers=[],$rooms=[];
    public const PERSON_ROLES=['estudiantes','computacions','docentes','administrativos','cliservicios','clicopys','clipracticos','proveedors'];
    public const RECORDS=['inscripciones','matriculacions','programacions','programacioncoms','clases','clasecoms','sesions','sesioncoms',
        'pagos','ventas','detalle_ventas','otroingresos','observacions','files','licencias','calificacions','estudiante_grado','ninacos',
        'crm_seguimientos','crm_actividad_detalles','mensajeados','comentarios','potencials','periodables','felicitados','contactoparaderivars','aulas'];
    public const PARENTS=[
        'inscripciones'=>['estudiante_id'=>'estudiantes'],'matriculacions'=>['computacion_id'=>'computacions'],
        'programacions'=>['inscripcione_id'=>'inscripciones'],'programacioncoms'=>['matriculacion_id'=>'matriculacions'],
        'clases'=>['programacion_id'=>'programacions'],'clasecoms'=>['programacioncom_id'=>'programacioncoms'],
        'sesions'=>['inscripcione_id'=>'inscripciones'],'sesioncoms'=>['matriculacion_id'=>'matriculacions'],
        'detalle_ventas'=>['venta_id'=>'ventas'],'estudiante_grado'=>['estudiante_id'=>'estudiantes'],
        'crm_actividad_detalles'=>['observacion_id'=>'observacions'],
    ];
    public function reset(): void { $this->enabled=false; $this->user=null; $this->active=null; $this->family=false; $this->people=[]; $this->contacts=[]; $this->teachers=[];$this->rooms=[]; }
    public function start(User $user,$request): void
    {
        $this->user=$user; $this->enabled=true; $this->family=app(AccountSecurity::class)->family($user);
        if ($this->family) {
            $this->people=array_filter([(int)$user->persona_id]);
            if ($user->hasRole('Padre')&&$user->persona_id) { $this->people=array_unique(array_merge($this->people,DB::table('persona_persona')->where('persona_id_apoderado',$user->persona_id)->pluck('persona_id')->all())); }
            $this->contacts=$this->people;
            if ($request->routeIs('padre.*','foto.*')) {
                foreach (['programacions'=>['inscripciones','inscripcione_id','estudiantes','estudiante_id'],
                    'programacioncoms'=>['matriculacions','matriculacion_id','computacions','computacion_id'],
                    'sesions'=>['inscripciones','inscripcione_id','estudiantes','estudiante_id'],
                    'sesioncoms'=>['matriculacions','matriculacion_id','computacions','computacion_id']] as $source=>$join) {
                    if (!Schema::hasTable($source))continue;[$enrollment,$key,$roles,$roleKey]=$join;
                    $related=DB::table($source.' as p')->join($enrollment.' as e','e.id','=','p.'.$key)->join($roles.' as r','r.id','=','e.'.$roleKey)->whereIn('r.persona_id',$this->people)->get(['p.docente_id','p.aula_id']);
                    $this->teachers=array_merge($this->teachers,$related->pluck('docente_id')->all());
                    $this->rooms=array_merge($this->rooms,$related->pluck('aula_id')->all());
                }
                $this->teachers=array_values(array_unique(array_filter($this->teachers)));
                $this->contacts=array_merge($this->contacts,DB::table('docentes')->whereIn('id',$this->teachers)->pluck('persona_id')->all());
            }
            return;
        }
        $ids=$this->allowedIds($user); $selected=$request->session()->get('sede_activa');
        if ($user->hasRole('Admin')&&($selected===null||(int)$selected===0)) { $this->active=null; return; }
        $this->active=in_array((int)$selected,$ids,true)?(int)$selected:(in_array((int)$user->sede_id,$ids,true)?(int)$user->sede_id:($ids[0]??null));
        $request->session()->put('sede_activa',$this->active);
    }
    public function active(): ?int { return $this->active; }
    public function selectForCreation(Request $request): void
    {
        if (!$this->enabled) { return; }
        abort_if($this->family,403);
        $data=$request->validate(['sede_id'=>'nullable|integer|min:1']);
        $sede=isset($data['sede_id'])?(int)$data['sede_id']:$this->active;
        if (!$sede) {
            throw ValidationException::withMessages(['sede_id'=>'Seleccione la Sucursal de la inscripción. «Todas · Admin» permite consultar; para registrar debe elegir una Sucursal.']);
        }
        abort_unless(in_array($sede,$this->allowedIds($this->user),true),403,'Sucursal no autorizada.');
        $request->session()->put('sede_activa',$sede);
        $this->start($this->user,$request);
    }
    public function canReadPhoto(array $file,ProfilePhotoService $photos): bool
    {
        if (!$this->enabled||$this->user->hasRole('Admin')) { return true; }
        try { $own=$photos->file($this->user->foto); if($own&&$own['path']===$file['path'])return true; } catch(\App\Exceptions\GoogleContactsException $e) {}
        $basename=basename($file['path']);
        $candidates=\App\Models\Persona::where(function($q)use($basename){$q->where('foto','like','%'.$basename)->orWhere('foto','like','%'.rawurlencode($basename));})->limit(100)->get(['id','foto']);
        foreach($candidates as $person){try{$candidate=$photos->file($person->foto);if($candidate&&$candidate['path']===$file['path'])return true;}catch(\App\Exceptions\GoogleContactsException $e){}}
        if(!$this->family&&$this->user->can('Listar Usuarios')){
            foreach(User::where(function($q)use($basename){$q->where('foto','like','%'.$basename)->orWhere('foto','like','%'.rawurlencode($basename));})->limit(100)->get(['id','foto']) as $user){
                try{$candidate=$photos->file($user->foto);if($candidate&&$candidate['path']===$file['path'])return true;}catch(\App\Exceptions\GoogleContactsException $e){}
            }
        }
        return false;
    }
    public function enabled(): bool { return $this->enabled; }
    public function contextKey(): string { return $this->enabled?implode(':',[$this->user->id,$this->active??'all',(int)$this->family,implode(',',$this->people),implode(',',$this->contacts),implode(',',$this->rooms)]):'off'; }
    public function allowedIds(User $user): array
    {
        if ($user->hasRole('Admin')) { return CrmSede::where('activa',true)->pluck('id')->map(fn($v)=>(int)$v)->all(); }
        return DB::table('sede_user')->join('crm_sedes','crm_sedes.id','=','sede_user.sede_id')->where('sede_user.user_id',$user->id)
            ->where('crm_sedes.activa',true)->pluck('crm_sedes.id')->map(fn($v)=>(int)$v)->all();
    }
    public static function query($query)
    {
        return \App\Support\SedeQueryBuilder::wrap($query);
    }
    public static function aggregate(string $table,string $expression,string $foreign,string $owner)
    {
        return self::query(DB::table($table)->selectRaw($expression)->whereColumn($foreign,$owner));
    }
    public function authorizeReference(string $class,$id): void
    {
        if (!$this->enabled) { return; }
        abort_unless(preg_match('/^App\\\\Models\\\\[A-Za-z]+$/D',$class)&&class_exists($class)&&is_subclass_of($class,Model::class),422,'Tipo de registro relacionado no permitido.');
        $model=new $class;
        abort_unless(in_array($model->getTable(),array_merge(self::PERSON_ROLES,self::RECORDS,['personas','prospectos']),true),422,'Tipo de registro relacionado no permitido.');
        $query=DB::table($model->getTable())->where($model->getKeyName(),$id);$this->constrain($query);abort_unless($query->exists(),403,'Registro relacionado de otra Sucursal o no autorizado.');
    }
    public function constrain($query): void
    {
        if (!$this->enabled||(!$this->family&&$this->user->hasRole('Admin')&&$this->active===null)) { return; }
        $sources=[$query->from];
        if (!$this->family) { foreach ($query->joins??[] as $join) { $sources[]=$join->table; } }
        $sources=array_unique(array_filter($sources,'is_string'));
        // Group existing OR conditions so a search can never bypass the access predicate.
        if ($query->wheres) { $nested=$query->forNestedWhere(); $nested->wheres=$query->wheres; $query->wheres=[['type'=>'Nested','query'=>$nested,'boolean'=>'and']]; }
        foreach ($sources as $source) {
            if (!preg_match('/^([a-z_]+)(?:\s+(?:as\s+)?([a-z_]+))?$/i',$source,$match)) { continue; }
            $table=strtolower($match[1]); $alias=$match[2]??$table;
            if ($this->family) { $this->familyFilter($query,$table,$alias); }
            else { $this->staffFilter($query,$table,$alias); }
        }
    }
    private function staffFilter($q,string $table,string $alias): void
    {
        if($table==='users'&&!$this->user->hasRole('Admin')){
            $q->where(function($x)use($alias){
                $x->where($alias.'.id',$this->user->id)->orWhereExists(fn($p)=>$p->selectRaw('1')->from('sede_user as su')->whereColumn('su.user_id',$alias.'.id')->where('su.sede_id',$this->active??0));
                if(config('user_access.legacy_records_visible'))$x->orWhereNotExists(fn($p)=>$p->selectRaw('1')->from('sede_user as su')->whereColumn('su.user_id',$alias.'.id'));
            });
        } elseif ($table==='personas') {
            $q->where(function ($x) use ($alias) {
                $x->whereExists(function ($p) use ($alias) { $p->selectRaw('1')->from('persona_sede as ps')->whereColumn('ps.persona_id',$alias.'.id')->where('ps.sede_id',$this->active??0); });
                if (config('user_access.legacy_records_visible')) { $x->orWhereNotExists(fn($p)=>$p->selectRaw('1')->from('persona_sede as ps')->whereColumn('ps.persona_id',$alias.'.id')); }
            });
        } elseif (in_array($table,self::PERSON_ROLES,true)||$table==='telefonos'||$table==='crm_seguimientos') {
            $q->whereIn($alias.'.persona_id',function ($p) { $p->select('id')->from('personas'); $this->staffFilter($p,'personas','personas'); });
        } elseif ($table==='prospectos') {
            $q->where(function ($p) use ($alias) { $p->where($alias.'.crm_sede_id',$this->active??0); if(config('user_access.legacy_records_visible')) { $p->orWhereNull($alias.'.crm_sede_id'); } });
        } elseif (in_array($table,self::RECORDS,true)) {
            $key=$table==='crm_actividad_detalles'?'observacion_id':'id';
            $q->where(function ($x) use ($table,$alias,$key) {
                $x->whereExists(fn($p)=>$p->selectRaw('1')->from('sede_registros as sr')->where('sr.tabla',$table)->whereColumn('sr.registro_id',$alias.'.'.$key)->where('sr.sede_id',$this->active??0));
                if (config('user_access.legacy_records_visible')) { $x->orWhereNotExists(fn($p)=>$p->selectRaw('1')->from('sede_registros as sr')->where('sr.tabla',$table)->whereColumn('sr.registro_id',$alias.'.'.$key)); }
            });
        }
        if($q instanceof \App\Support\SedeQueryBuilder)$q->rememberSedeGuard();
    }
    private function familyFilter($q,string $table,string $alias,int $depth=0): void
    {
        if ($depth>6) { $q->whereRaw('1=0'); return; }
        if ($table==='personas') { $q->whereIn($alias.'.id',$this->contacts); }
        elseif ($table==='docentes') { $q->whereIn($alias.'.id',$this->teachers); }
        elseif ($table==='aulas') { $q->whereIn($alias.'.id',array_filter($this->rooms)); }
        elseif (in_array($table,self::PERSON_ROLES,true)||in_array($table,['telefonos','crm_seguimientos'],true)) { $q->whereIn($alias.'.persona_id',$this->people); }
        elseif (isset(self::PARENTS[$table])) {
            foreach (self::PARENTS[$table] as $key=>$parent) { $q->whereIn($alias.'.'.$key,function ($p) use ($parent,$depth) { $p->select('id')->from($parent); $this->familyFilter($p,$parent,$parent,$depth+1); }); }
        } elseif ($table==='pagos') {
            $q->where(function ($x) use ($alias,$depth) {
                foreach (['Inscripcione'=>'inscripciones','Matriculacion'=>'matriculacions'] as $class=>$parent) {
                    $x->orWhere(function ($p) use ($alias,$class,$parent,$depth) { $p->where($alias.'.pagable_type','App\\Models\\'.$class)->whereIn($alias.'.pagable_id',function ($s) use ($parent,$depth) { $s->select('id')->from($parent); $this->familyFilter($s,$parent,$parent,$depth+1); }); });
                }
            });
        } elseif (in_array($table,self::RECORDS,true)||$table==='prospectos') { $q->whereRaw('1=0'); }
        if($q instanceof \App\Support\SedeQueryBuilder)$q->rememberSedeGuard();
    }
    public function prepare(Model $record): ?int
    {
        if (!$this->enabled) { return null; }
        $table=$record->getTable();
        if (!in_array($table,array_merge(self::RECORDS,self::PERSON_ROLES,['personas','prospectos','telefonos']),true)) { return null; }
        abort_if($this->family,403,'Esta cuenta no puede registrar operaciones del personal.');
        if ($record->exists) {
            $query=DB::table($table)->where($record->getKeyName(),$record->getKey()); $this->constrain($query);
            abort_unless($query->exists(),403,'Este registro no está autorizado en la Sucursal activa.');
        }
        $destination=$this->active;$linkedHistory=false;
        if(!$record->exists&&$destination===null&&$this->user->hasRole('Admin')){
            // An admin may append history across branches; inherit its existing parent's attribution.
            if($table==='observacions'&&$record->observable_type&&$record->observable_id){
                $this->authorizeReference($record->observable_type,$record->observable_id);
                $parent=new $record->observable_type;
                $destination=$parent->getTable()==='prospectos'?DB::table('prospectos')->where('id',$record->observable_id)->value('crm_sede_id'):
                    DB::table('sede_registros')->where('tabla',$parent->getTable())->where('registro_id',$record->observable_id)->value('sede_id');
                $linkedHistory=true;
            }elseif($table==='crm_actividad_detalles'&&$record->observacion_id){
                $this->authorizeReference(\App\Models\Observacion::class,$record->observacion_id);
                $destination=DB::table('sede_registros')->where('tabla','observacions')->where('registro_id',$record->observacion_id)->value('sede_id');$linkedHistory=true;
            }
        }
        if (!$record->exists&&$destination===null&&!$linkedHistory) { throw ValidationException::withMessages(['sede_id'=>'Seleccione una Sucursal activa antes de registrar la operación.']); }
        $foreign=['persona_id'=>'personas','estudiante_id'=>'estudiantes','computacion_id'=>'computacions','docente_id'=>'docentes',
            'inscripcione_id'=>'inscripciones','matriculacion_id'=>'matriculacions','programacion_id'=>'programacions','programacioncom_id'=>'programacioncoms','aula_id'=>'aulas'];
        foreach (array_merge(self::PARENTS[$table]??[],$foreign) as $key=>$parent) {
            if (!$record->$key) { continue; }
            $q=DB::table($parent)->where('id',$record->$key); $this->constrain($q);
            abort_unless($q->exists(),403,'El registro relacionado no está autorizado en esta Sucursal.');
        }
        foreach(['observable','fileable','periodable','licenciable','mensajeable','calificable'] as $morph){
            if($record->getAttribute($morph.'_type')&&$record->getAttribute($morph.'_id'))$this->authorizeReference($record->getAttribute($morph.'_type'),$record->getAttribute($morph.'_id'));
        }
        if ($table==='pagos') {
            $classes=['App\\Models\\Inscripcione'=>'inscripciones','App\\Models\\Matriculacion'=>'matriculacions','App\\Models\\Venta'=>'ventas','App\\Models\\Cliservicio'=>'cliservicios','App\\Models\\Clicopy'=>'clicopys','App\\Models\\Periodable'=>'periodables','App\\Models\\Docente'=>'docentes','App\\Models\\Administrativo'=>'administrativos'];
            $type=$record->pagable_type;
            abort_unless(isset($classes[$type]),422,'Tipo de pago no permitido.');
            // The legacy period form uses the period ID together with the worker's morph type.
            $period=request()->route('periodable');
            if($period instanceof \App\Models\Periodable&&(int)$period->id===(int)$record->pagable_id&&$period->periodable_type===$type){
                $this->authorizeReference(\App\Models\Periodable::class,$period->id);
            }else{
                $this->authorizeReference($type,$record->pagable_id);
            }
        }
        if ($table==='prospectos') {
            if ($record->crm_sede_id&&(int)$record->crm_sede_id!==$this->active&&!$this->user->hasRole('Admin')) { abort(403,'Sucursal no autorizada.'); }
            if (!$record->exists&&!$record->crm_sede_id) { $record->crm_sede_id=$this->active; }
        }
        return $destination===null?null:(int)$destination;
    }
    public function attribute(Model $record,?int $sede): void
    {
        if (!$this->enabled||!$sede) { return; }
        $table=$record->getTable();
        if ($table==='personas') {
            DB::table('persona_sede')->insertOrIgnore(['persona_id'=>$record->id,'sede_id'=>$sede,'created_at'=>now(),'updated_at'=>now()]);
        } elseif (in_array($table,self::RECORDS,true)) {
            DB::table('sede_registros')->insert(['tabla'=>$table,'registro_id'=>$record->getKey(),'sede_id'=>$sede,'created_at'=>now()]);
        }
    }
}
