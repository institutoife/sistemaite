<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendCrmOwnershipAndInterests extends Migration
{
    public function up()
    {
        Schema::table('prospectos', function (Blueprint $table) {
            foreach (['creado_por','responsable_inicial_id','convertido_por','responsable_conversion_id'] as $field) {
                $table->unsignedBigInteger($field)->nullable()->index();
            }
            $table->dateTime('ultimo_seguimiento_at')->nullable();
            $table->dateTime('crm_services_migrated_at')->nullable();
            $table->index(['crm_status','responsable_id'],'prospectos_crm_operativo');
        });
        Schema::create('interest_prospecto', function (Blueprint $table) {
            $table->unsignedBigInteger('prospecto_id');
            $table->unsignedBigInteger('interest_id');
            $table->primary(['prospecto_id','interest_id']);
            $table->index(['interest_id','prospecto_id']);
        });
        Schema::table('crm_actividad_detalles', function (Blueprint $table) {
            foreach (['actor_id','responsable_comercial_id','crm_sede_id'] as $field) { $table->unsignedBigInteger($field)->nullable(); }
            $table->json('servicios_ids')->nullable();
            $table->dateTime('registrado_at')->nullable();
            $table->boolean('asignacion_comercial')->default(false);
            $table->index(['actor_id','ocurrio_at'],'crm_actividad_actor_fecha');
            $table->index(['responsable_comercial_id','ocurrio_at'],'crm_actividad_responsable_fecha');
        });
        Schema::table('crm_etapas', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('observacions', function (Blueprint $table) { $table->index(['observable_type','observable_id'],'observacions_crm_subject'); });
    }

    public function down()
    {
        Schema::table('observacions', function (Blueprint $table) { $table->dropIndex('observacions_crm_subject'); });
        Schema::table('crm_etapas', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('crm_actividad_detalles', function (Blueprint $table) {
            $table->dropIndex('crm_actividad_actor_fecha'); $table->dropIndex('crm_actividad_responsable_fecha');
            $table->dropColumn(['actor_id','responsable_comercial_id','crm_sede_id','servicios_ids','registrado_at','asignacion_comercial']);
        });
        Schema::dropIfExists('interest_prospecto');
        Schema::table('prospectos', function (Blueprint $table) {
            foreach (['creado_por','responsable_inicial_id','convertido_por','responsable_conversion_id'] as $field) { $table->dropIndex([$field]); }
            $table->dropIndex('prospectos_crm_operativo');
            $table->dropColumn(['creado_por','responsable_inicial_id','convertido_por','responsable_conversion_id','ultimo_seguimiento_at','crm_services_migrated_at']);
        });
    }
}
