<?php
namespace Tests;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
trait CrmFixtures
{
    protected function prepareCrmFixtures(): void
    {
        if (!app()->environment('testing')||config('database.default')!=='sqlite'||config('database.connections.sqlite.database')!==':memory:') { throw new \RuntimeException('CRM tests require isolated SQLite.'); }
        Schema::table('personas',function (Blueprint $table) { $table->boolean('votos')->nullable()->default(true); $table->date('vuelvefecha')->nullable(); $table->unsignedBigInteger('como_id')->nullable(); });
        foreach (['estados'=>'estado','comos'=>'como','grados'=>'grado'] as $name=>$field) { Schema::create($name,function (Blueprint $table) use ($field) { $table->id(); $table->string($field); $table->timestamps(); }); }
        foreach (['HABILITADO','PROSPECTO','SEGUIMIENTO','CANCELADO','PERDIDO','ESPERANUEVO'] as $i=>$state) { DB::table('estados')->insert(['id'=>$i+1,'estado'=>$state]); }
        foreach ([
            '2022_03_15_090348_create_interests_table.php'=>'CreateInterestsTable',
            '2022_03_15_174505_create_interest_persona_table.php'=>'CreateInterestPersonaTable',
            '2021_04_29_030205_create_observacions_table.php'=>'CreateObservacionsTable',
            '2026_04_03_120000_create_crm_seguimientos_table.php'=>'CreateCrmSeguimientosTable',
            '2026_10_29_000001_extend_prospectos_crm.php'=>'ExtendProspectosCrm',
        ] as $file=>$class) { require_once database_path('migrations/'.$file); (new $class)->up(); }
        DB::table('interests')->insert(['id'=>1,'interest'=>'Computación','descripcion'=>'Prueba']);
        DB::table('grados')->insert(['id'=>1,'grado'=>'Primaria']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name'=>'Secretaria','guard_name'=>'web']);
        $this->seed(\Database\Seeders\CrmSeeder::class);
    }
}
