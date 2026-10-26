<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendPersonasGoogleContacts extends Migration
{
    public function up()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedBigInteger('gcontact_account_id')->nullable();
            $table->foreign('gcontact_account_id')->references('id')->on('google_contact_accounts');
            $table->string('gcontact_sync_status', 20)->default('idle');
            $table->unsignedBigInteger('gcontact_revision')->default(0);
            $table->string('gcontact_error_code', 50)->nullable();
            $table->timestamp('gcontact_synced_at')->nullable();
            $table->timestamp('gcontact_create_started_at')->nullable();
            $table->char('gcontact_payload_hash', 64)->nullable();
            $table->index(['gcontact_sync_pending', 'gcontact_sync_status'], 'personas_gcontact_pending_index');
            // Nullable account IDs preserve historical links until verified against Google.
            $table->unique(['gcontact_account_id', 'resourseName'], 'personas_gcontact_resource_unique');
        });
    }

    public function down()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropUnique('personas_gcontact_resource_unique');
            $table->dropIndex('personas_gcontact_pending_index');
            $table->dropForeign(['gcontact_account_id']);
            $table->dropColumn(['gcontact_account_id', 'gcontact_sync_status', 'gcontact_revision',
                'gcontact_error_code', 'gcontact_synced_at', 'gcontact_create_started_at', 'gcontact_payload_hash']);
        });
    }
}
