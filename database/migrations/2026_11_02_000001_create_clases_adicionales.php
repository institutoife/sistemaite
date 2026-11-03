<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClasesAdicionales extends Migration
{
    public function up()
    {
        // Additive tables: no ALTER or backfill of the legacy dates/programming tables.
        Schema::create('clases_adicionales', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->uuid('solicitud')->unique();
            foreach (['programacion_id' => 'programacions', 'programacioncom_id' => 'programacioncoms',
                'original_programacion_id' => 'programacions', 'original_programacioncom_id' => 'programacioncoms'] as $key => $parent) {
                $table->unsignedBigInteger($key)->nullable()->unique();
                $table->foreign($key, 'ca_'.$key.'_fk')->references('id')->on($parent)->onDelete('restrict');
            }
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre');
            $table->text('motivo');
            $table->timestamps();
        });
        Schema::create('clase_asistencia_eventos', function (Blueprint $table) {
            $table->id();
            foreach (['programacion_id' => 'programacions', 'programacioncom_id' => 'programacioncoms'] as $key => $parent) {
                $table->unsignedBigInteger($key)->nullable()->index();
                $table->foreign($key, 'cae_'.$key.'_fk')->references('id')->on($parent)->onDelete('restrict');
            }
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre');
            $table->string('accion', 30);
            $table->json('antes')->nullable();
            $table->json('despues');
            $table->text('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Never silently discard the audit trail on rollback.
        if (\Illuminate\Support\Facades\DB::table('clases_adicionales')->exists()
            || \Illuminate\Support\Facades\DB::table('clase_asistencia_eventos')->exists()) {
            throw new \RuntimeException('Hay historial de asistencia; conserve estas tablas y revierta únicamente el código.');
        }
        Schema::dropIfExists('clase_asistencia_eventos');
        Schema::dropIfExists('clases_adicionales');
    }
}
