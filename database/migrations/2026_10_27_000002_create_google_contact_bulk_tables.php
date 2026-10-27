<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleContactBulkTables extends Migration
{
    public function up()
    {
        Schema::create('google_contact_bulk_runs', function (Blueprint $table) {
            $table->id();
            $table->string('active_slot', 40)->nullable()->unique();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('source', 20);
            $table->boolean('include_synced')->default(false);
            $table->unsignedInteger('requested_limit')->default(1000);
            $table->json('max_ids');
            $table->json('cursors');
            $table->unsignedInteger('candidate_count')->default(0);
            $table->unsignedInteger('scanned')->default(0);
            $table->unsignedInteger('queued')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->string('status', 20)->default('preparing');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('google_contact_bulk_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->string('source', 20);
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('revision');
            $table->unique(['run_id', 'source', 'record_id'], 'gcontact_bulk_record_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('google_contact_bulk_items');
        Schema::dropIfExists('google_contact_bulk_runs');
    }
}
