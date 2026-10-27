<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonPhoneVerificationTables extends Migration
{
    public function up()
    {
        Schema::create('person_phone_verifications', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id')->primary();
            $table->char('phone_hash', 64);
            $table->timestamp('verified_at');
            $table->unsignedBigInteger('verified_by')->nullable();
        });
        Schema::create('person_phone_deferrals', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id');
            $table->unsignedBigInteger('user_id');
            $table->char('phone_hash', 64);
            $table->timestamp('deferred_until');
            $table->primary(['persona_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('person_phone_deferrals');
        Schema::dropIfExists('person_phone_verifications');
    }
}
