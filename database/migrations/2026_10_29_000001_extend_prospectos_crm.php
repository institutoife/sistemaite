<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class ExtendProspectosCrm extends Migration
{
    public function up()
    {
        Schema::create('crm_etapas', function (Blueprint $table) {
            $table->id(); $table->string('nombre',80); $table->string('codigo',40)->nullable()->unique();
            $table->string('color',7)->default('#375F7A'); $table->unsignedInteger('orden')->default(0);
            $table->boolean('activa')->default(true); $table->string('inicial',20)->nullable()->unique();
            $table->boolean('conversion')->default(false); $table->unsignedInteger('version')->default(0); $table->timestamps();
        });
        Schema::create('crm_sedes', function (Blueprint $table) {
            $table->id(); $table->string('nombre',100)->unique(); $table->boolean('activa')->default(true); $table->timestamps();
        });
        Schema::table('prospectos', function (Blueprint $table) {
            $table->string('nombre',150)->nullable(); $table->string('telefono_normalizado',20)->nullable()->index();
            foreach (['interest_id','como_id','grado_id','crm_sede_id','responsable_id','crm_etapa_id','persona_id',
                'estudiante_persona_id','inscripcione_id','matriculacion_id','baja_por'] as $field) { $table->unsignedBigInteger($field)->nullable(); }
            $table->unsignedBigInteger('legacy_persona_id')->nullable()->unique();
            $table->string('estudiante_nombre',150)->nullable(); $table->text('observacion')->nullable();
            $table->string('crm_status',20)->default('activo'); $table->boolean('no_contactar')->default(false);
            $table->unsignedBigInteger('crm_version')->default(0);
            foreach (['etapa_desde','proximo_contacto_at','contactado_at','convertido_at','baja_at'] as $field) { $table->dateTime($field)->nullable(); }
            $table->string('ultimo_resultado',40)->nullable(); $table->string('motivo_baja',80)->nullable();
            $table->index(['crm_status','crm_etapa_id'],'prospectos_crm_estado_etapa');
            $table->index(['responsable_id','proximo_contacto_at'],'prospectos_crm_agenda');
            $table->index('persona_id'); $table->index('convertido_at'); $table->index('created_at');
        });
        // Observacion retains text, subject and audit users; this table adds typed CRM metadata.
        Schema::create('crm_actividad_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('observacion_id')->primary(); $table->string('tipo',40);
            $table->dateTime('ocurrio_at'); $table->unsignedBigInteger('responsable_id');
            $table->string('resultado',200)->nullable(); $table->string('proxima_accion',200)->nullable();
            $table->dateTime('proximo_contacto_at')->nullable(); $table->json('datos')->nullable();
            $table->index(['tipo','ocurrio_at'],'crm_actividad_tipo_fecha');
        });
    }
    public function down()
    {
        Schema::dropIfExists('crm_actividad_detalles');
        Schema::table('prospectos', function (Blueprint $table) {
            $table->dropUnique(['legacy_persona_id']);
            foreach (['prospectos_crm_estado_etapa','prospectos_crm_agenda','prospectos_persona_id_index',
                'prospectos_convertido_at_index','prospectos_created_at_index','prospectos_telefono_normalizado_index'] as $index) { $table->dropIndex($index); }
            $table->dropColumn(['nombre','telefono_normalizado','interest_id','como_id','grado_id','crm_sede_id','responsable_id',
                'crm_etapa_id','persona_id','estudiante_persona_id','inscripcione_id','matriculacion_id','baja_por','legacy_persona_id',
                'estudiante_nombre','observacion','crm_status','no_contactar','crm_version','etapa_desde','proximo_contacto_at',
                'contactado_at','convertido_at','baja_at','ultimo_resultado','motivo_baja']);
        });
        Schema::dropIfExists('crm_sedes'); Schema::dropIfExists('crm_etapas');
    }
}
