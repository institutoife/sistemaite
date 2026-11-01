<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\Permission\Traits\HasRoles;
/**
 * Class User
 *
 * @property $id
 * @property $name
 * @property $email
 * @property $email_verified_at
 * @property $password
 * @property $foto
 * @property $remember_token
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;
   
    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    //protected $table ="users";
    protected $fillable = ['name','email', 'password','foto'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'api_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'suspended_until'=>'datetime','temporary_password_expires_at'=>'datetime','password_changed_at'=>'datetime',
        'last_login_at'=>'datetime','force_password_change'=>'boolean','access_schedule_enabled'=>'boolean',
        'two_factor_required'=>'boolean','two_factor_confirmed_at'=>'datetime',
        'two_factor_secret'=>'encrypted','two_factor_recovery_codes'=>'encrypted:array',
    ];

    public function adminlte_image()
    {
        return app(\App\Services\ProfilePhotoService::class)->forUser($this);
    }

    public function adminlte_desc()
    {
        $rol = $this->roles()->pluck('name')->first();
        return $rol ?: 'Usuario';
    }

    public function adminlte_profile_url()
    {
        return "Perfil";
    }
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public function sede() { return $this->belongsTo(CrmSede::class,'sede_id'); }
    public function sedes() { return $this->belongsToMany(CrmSede::class,'sede_user','user_id','sede_id')->withTimestamps(); }
    public function accessAudits() { return $this->hasMany(UserAccessAudit::class,'subject_user_id')->latest('id'); }
    public function getEffectiveAccessStatusAttribute(): string
    {
        return $this->access_status==='suspended'&&$this->suspended_until&&now()->gte($this->suspended_until)?'active':($this->access_status?:'active');
    }
    public function scopeCurrentlyActive($query)
    {
        return $query->where(fn($q)=>$q->where('access_status','active')->orWhere(fn($s)=>$s->where('access_status','suspended')->whereNotNull('suspended_until')->where('suspended_until','<=',now())));
    }
    protected function newBaseQueryBuilder() { return \App\Services\SedeAccess::query(parent::newBaseQueryBuilder()); }
    protected function performInsert(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $this->getConnection()->transaction(function()use($query){
            if ($this->persona_id&&app(\App\Services\AccountSecurity::class)->ready()) {
                \Illuminate\Support\Facades\DB::table('personas')->where('id',$this->persona_id)->lockForUpdate()->first();
                if (static::where('persona_id',$this->persona_id)->exists()) { throw \Illuminate\Validation\ValidationException::withMessages(['persona_id'=>'La Persona ya tiene una cuenta de usuario.']); }
            }
            return parent::performInsert($query);
        });
    }
    
    public function administrativos(){
        return $this->morphedByMany(Administrativo::class,'userable');
    } 
    public function asignaturas(){
        return $this->morphedByMany(Asignatura::class,'userable');
    } 
    public function aulas(){
        return $this->morphedByMany(Aula::class,'userable');
    }
    public function billetes(){
        return $this->morphedByMany(Billete::class,'userable');
    }
    public function calificaciones(){
        return $this->morphedByMany(Calificacion::class,'userable');
    }
    
    public function carreras(){
        return $this->morphedByMany(Carrera::class,'userable');
    }
    
    public function ciudades(){
        return $this->morphedByMany(Ciudad::class,'userable');
    }
    
    public function clases(){
        return $this->morphedByMany(Clase::class,'userable');
    } 
    
    public function clasecoms(){
        return $this->morphedByMany(Clasecom::class,'userable');
    } 
    public function clicopys(){
        return $this->morphedByMany(Clicopy::class,'userable');
    } 
    public function clipracticos(){
        return $this->morphedByMany(Clipractico::class,'userable');
    } 
    public function cliservicios(){
        return $this->morphedByMany(Cliservicio::class,'userable');
    } 
    public function colegios(){
        return $this->morphedByMany(Colegio::class,'userable');
    } 

    public function computaciones(){
        return $this->morphedByMany(Computacion::class,'userable');
    }
    public function departamentos(){
        return $this->morphedByMany(Departamento::class,'userable');
    } 
    public function dias(){
        return $this->morphedByMany(Dia::class,'userable');
    } 
    public function docentes(){
        return $this->morphedByMany(Docente::class,'userable');
    } 
    public function estados(){
        return $this->morphedByMany(Estado::class,'userable');
    } 
    public function eventos(){
        return $this->morphedByMany(Estado::class,'userable');
    } 
    public function estudiantes(){
        return $this->morphedByMany(Estudiante::class,'userable');
    } 
    public function feriados(){
        return $this->morphedByMany(Feriado::class,'userable');
    } 
    public function files(){
        return $this->morphedByMany(File::class,'userable');
    } 
    public function gestiones(){
        return $this->morphedByMany(Gestion::class,'userable');
    } 
    public function grados(){
        return $this->morphedByMany(Grado::class,'userable');
    } 

    public function inscripciones(){
        return $this->morphedByMany(Inscripcione::class,'userable');
    } 
    public function interests(){
        return $this->morphedByMany(Interest::class,'userable');
    } 
    public function licencias(){
        return $this->morphedByMany(Licencia::class,'userable');
    } 
    public function materias(){
        return $this->morphedByMany(Materia::class,'userable');
    } 
    public function matriculaciones(){
        return $this->morphedByMany(Matriculacion::class,'userable');
    } 
    public function mensajes(){
        return $this->morphedByMany(Mensaje::class,'userable');
    } 
    public function modalidades(){
        return $this->morphedByMany(Modalidad::class,'userable');
    } 
    public function motivos(){
        return $this->morphedByMany(Motivo::class,'userable');
    } 

    public function municipios(){
        return $this->morphedByMany(Municipio::class,'userable');
    } 

    public function niveles(){
        return $this->morphedByMany(Nivel::class,'userable');
    } 
    public function observaciones(){
        return $this->morphedByMany(Observacion::class,'userable');
    } 

    public function pagos(){
        return $this->morphedByMany(Pago::class,'userable');
    } 

    public function ventas(){
        return $this->morphedByMany(Venta::class,'userable');
    }

    public function paises(){
        return $this->morphedByMany(Pais::class,'userable');
    } 

    public function personas(){
        return $this->morphedByMany(Persona::class,'userable');
    }

    public function potenciales(){
        return $this->morphedByMany(Potencial::class,'userable');
    } 

    public function proveedores(){
        return $this->morphedByMany(Proveedor::class,'userable');
    } 

    public function provincias(){
        return $this->morphedByMany(Provincia::class,'userable');
    } 

    public function temas(){
        return $this->morphedByMany(Tema::class,'userable');
    } 

    public function tipofiles(){
        return $this->morphedByMany(Tipofile::class,'userable');
    } 

    public function tipomotivos(){
        return $this->morphedByMany(Tipomotivo::class,'userable');
    } 
    public function zonas(){
        return $this->morphedByMany(Zona::class,'userable');
    } 
   
}
