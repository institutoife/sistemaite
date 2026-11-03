<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddTopicToClasesAdicionales extends Migration
{
    public function up()
    {
        Schema::table('clases_adicionales',function(Blueprint $table){
            $table->foreignId('tema_id')->nullable()->constrained('temas')->nullOnDelete();
        });
    }

    public function down()
    {
        if (DB::table('clases_adicionales')->whereNotNull('tema_id')->exists()) {
            throw new \RuntimeException('Hay temas de clases adicionales registrados; conserve esta información.');
        }
        Schema::table('clases_adicionales',function(Blueprint $table){$table->dropConstrainedForeignId('tema_id');});
    }
}
