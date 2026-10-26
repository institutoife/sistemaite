<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleContactPhotoHash extends Migration
{
    public function up()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->char('gcontact_photo_hash', 64)->nullable();
        });
    }

    public function down()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('gcontact_photo_hash');
        });
    }
}
