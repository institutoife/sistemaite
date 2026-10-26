<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleContactAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('google_contact_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 20)->unique();
            $table->string('google_id')->unique();
            $table->string('email')->nullable();
            $table->uuid('installation_id')->unique();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('requires_reconnection')->default(false);
            $table->timestamp('disconnected_at')->nullable();
            $table->unsignedBigInteger('connected_by')->nullable();
            $table->foreign('connected_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('google_contact_accounts');
    }
}
