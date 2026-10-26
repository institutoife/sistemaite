<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleContactsPauseControls extends Migration
{
    public function up()
    {
        Schema::table('google_contact_accounts', function (Blueprint $table) {
            $table->timestamp('sync_paused_at')->nullable();
            $table->unsignedBigInteger('sync_paused_by')->nullable();
            $table->foreign('sync_paused_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('google_contact_accounts', function (Blueprint $table) {
            $table->dropForeign(['sync_paused_by']);
            $table->dropColumn(['sync_paused_at', 'sync_paused_by']);
        });
    }
}
