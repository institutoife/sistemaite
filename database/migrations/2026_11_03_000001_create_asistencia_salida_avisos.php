<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsistenciaSalidaAvisos extends Migration
{
    public function up()
    {
        // Notification preferences only. Never rebuild or backfill historical attendance tables.
        Schema::create('asistencia_salida_avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('canal', 12);
            $table->unsignedBigInteger('asistencia_id');
            $table->dateTime('avisado_at')->nullable();
            $table->dateTime('pospuesto_hasta')->nullable();
            $table->timestamps();
            $table->unique(['canal', 'asistencia_id', 'usuario_id'], 'asa_asistencia_usuario_unique');
        });
    }

    public function down()
    {
        // The append-only activity history remains in clase_asistencia_eventos.
        Schema::dropIfExists('asistencia_salida_avisos');
    }
}
