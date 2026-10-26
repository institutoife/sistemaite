<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendLeadsGoogleContacts extends Migration
{
    public function up()
    {
        foreach (['prospectos', 'comentarios'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->string('resourseName')->nullable();
                $table->string('etag')->nullable();
                $table->unsignedBigInteger('gcontact_account_id')->nullable();
                $table->foreign('gcontact_account_id')->references('id')->on('google_contact_accounts');
                $table->boolean('gcontact_sync_pending')->default(false);
                $table->string('gcontact_sync_status', 20)->default('idle');
                $table->unsignedBigInteger('gcontact_revision')->default(0);
                $table->unsignedInteger('gcontact_sync_attempts')->default(0);
                $table->text('gcontact_sync_error')->nullable();
                $table->timestamp('gcontact_sync_last_attempt_at')->nullable();
                $table->string('gcontact_error_code', 50)->nullable();
                $table->timestamp('gcontact_synced_at')->nullable();
                $table->timestamp('gcontact_create_started_at')->nullable();
                $table->char('gcontact_payload_hash', 64)->nullable();
                $table->index(['gcontact_sync_pending', 'gcontact_sync_status'], $name.'_gcontact_pending_index');
                $table->unique(['gcontact_account_id', 'resourseName'], $name.'_gcontact_resource_unique');
            });
        }
    }

    public function down()
    {
        foreach (['prospectos', 'comentarios'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropUnique($name.'_gcontact_resource_unique');
                $table->dropIndex($name.'_gcontact_pending_index');
                $table->dropForeign(['gcontact_account_id']);
                $table->dropColumn(['resourseName', 'etag', 'gcontact_account_id', 'gcontact_sync_pending',
                    'gcontact_sync_status', 'gcontact_revision', 'gcontact_sync_attempts', 'gcontact_sync_error',
                    'gcontact_sync_last_attempt_at', 'gcontact_error_code', 'gcontact_synced_at',
                    'gcontact_create_started_at', 'gcontact_payload_hash']);
            });
        }
    }
}
