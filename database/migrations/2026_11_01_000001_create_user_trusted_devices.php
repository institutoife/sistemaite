<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTrustedDevices extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_trusted_devices')) { return; }
        Schema::create('user_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('selector', 32)->unique();
            $table->string('token_hash', 64);
            $table->string('environment_hash', 64);
            $table->string('user_agent_hash', 64);
            $table->unsignedBigInteger('auth_version');
            $table->string('name', 100);
            $table->dateTime('last_used_at');
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at', 'expires_at'], 'user_trusted_devices_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_trusted_devices');
    }
}
