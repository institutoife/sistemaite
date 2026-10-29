<?php
namespace Database\Seeders;
use App\Models\CrmEtapa;
use App\Models\CrmSede;
use App\Models\Como;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class CrmSeeder extends Seeder
{
    public function run()
    {
        foreach (['nuevo'=>'Nuevo prospecto','contacto'=>'Primer contacto','interesado'=>'Interesado','informacion'=>'Información enviada',
            'seguimiento'=>'Seguimiento','inscripcion'=>'Inscripción pendiente','convertido'=>'Convertido en cliente'] as $code=>$name) {
            CrmEtapa::firstOrCreate(['codigo'=>$code],['nombre'=>$name,'orden'=>CrmEtapa::count()+1,'color'=>$code==='convertido'?'#26BAA5':'#375F7A',
                'inicial'=>$code==='nuevo'&&!CrmEtapa::whereNotNull('inicial')->exists()?'central':null,'conversion'=>$code==='convertido']);
        }
        foreach (['IFE Central','IFE Villa','IFE Plan'] as $name) { CrmSede::firstOrCreate(['nombre'=>$name]); }
        foreach (['TikTok','Facebook','Instagram','YouTube','Clapper','WhatsApp','Llamada telefónica','Referido','Visita presencial','Otro'] as $name) {
            if (!Como::whereRaw('LOWER(TRIM(como)) = ?',[mb_strtolower($name)])->exists()) { $row=new Como; $row->como=$name; $row->save(); }
        }
        foreach (['Listar Prospectos','Crear Prospectos','Editar Prospectos','Convertir Prospectos','Configurar CRM'] as $name) {
            $permission=Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
            foreach (Role::whereIn('name',$name==='Configurar CRM'?['Admin']:['Admin','Secretaria'])->where('guard_name','web')->get() as $role) { $role->givePermissionTo($permission); }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
