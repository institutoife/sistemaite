<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAccessControls extends Migration
{
    public function up()
    {
        Schema::table('users',function (Blueprint $table) {
            $table->unsignedBigInteger('sede_id')->nullable()->index();
            $table->string('access_status',20)->default('active');
            $table->dateTime('suspended_until')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->dateTime('temporary_password_expires_at')->nullable();
            $table->dateTime('password_changed_at')->nullable();
            $table->unsignedBigInteger('auth_version')->default(0);
            $table->dateTime('last_login_at')->nullable();
            $table->boolean('access_schedule_enabled')->default(false);
            $table->boolean('two_factor_required')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at')->nullable();
            $table->unsignedBigInteger('two_factor_last_step')->nullable();
        });
        Schema::create('sede_user',function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained('users');
            $table->foreignId('sede_id')->constrained('crm_sedes'); $table->timestamps();
            $table->unique(['user_id','sede_id']);
        });
        Schema::create('user_access_windows',function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained('users');
            $table->unsignedTinyInteger('weekday'); $table->time('starts_at'); $table->time('ends_at');
            $table->index(['user_id','weekday']);
        });
        Schema::create('user_access_exceptions',function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained('users');
            $table->dateTime('starts_at'); $table->dateTime('ends_at'); $table->boolean('allowed');
            $table->string('reason',500); $table->index(['user_id','starts_at','ends_at']);
        });
        Schema::create('user_access_audits',function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('subject_user_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('event',80)->index(); $table->json('before_values')->nullable();
            $table->json('after_values')->nullable(); $table->string('reason',500)->nullable();
            $table->dateTime('created_at')->index();
        });
        // Attribution is separate from legacy business tables: no historic rows are rewritten.
        Schema::create('sede_registros',function (Blueprint $table) {
            $table->id(); $table->string('tabla',80); $table->unsignedBigInteger('registro_id');
            $table->foreignId('sede_id')->constrained('crm_sedes'); $table->dateTime('created_at');
            $table->unique(['tabla','registro_id']); $table->index(['sede_id','tabla']);
        });
        Schema::create('persona_sede',function (Blueprint $table) {
            $table->id(); $table->foreignId('persona_id')->constrained('personas');
            $table->foreignId('sede_id')->constrained('crm_sedes'); $table->timestamps();
            $table->unique(['persona_id','sede_id']);
        });
        // Remove secrets from legacy credential notifications while retaining the notification history.
        if (Schema::hasTable('notifications')) {
            \Illuminate\Support\Facades\DB::table('notifications')->where('type',\App\Notifications\CredencialesPadreNotification::class)->orderBy('id')->chunkById(100,function($rows){
                foreach($rows as $row){
                    $data=json_decode($row->data,true);
                    if(!is_array($data)||!array_key_exists('password',$data))continue;
                    $user=isset($data['usuario_id'])?\Illuminate\Support\Facades\DB::table('users')->where('id',$data['usuario_id'])->first():null;
                    if($user&&is_string($data['password'])&&\Illuminate\Support\Facades\Hash::check($data['password'],$user->password)){
                        \Illuminate\Support\Facades\DB::table('users')->where('id',$user->id)->update(['force_password_change'=>true,'auth_version'=>\Illuminate\Support\Facades\DB::raw('auth_version + 1'),'remember_token'=>\Illuminate\Support\Str::random(60)]);
                        \Illuminate\Support\Facades\DB::table('user_access_audits')->insert(['subject_user_id'=>$user->id,'event'=>'legacy_credential_exposure','reason'=>'Contraseña vigente encontrada en notificación histórica; se exige cambio y se revocan sesiones.','created_at'=>now()]);
                    }
                    unset($data['password']);$data['requiere_entrega_segura']=true;
                    \Illuminate\Support\Facades\DB::table('notifications')->where('id',$row->id)->update(['data'=>json_encode($data,JSON_UNESCAPED_UNICODE)]);
                }
            });
        }
    }
    public function down()
    {
        foreach (['persona_sede','sede_registros','user_access_audits','user_access_exceptions','user_access_windows','sede_user'] as $table) { Schema::dropIfExists($table); }
        Schema::table('users',function (Blueprint $table) {
            $table->dropColumn(['sede_id','access_status','suspended_until','force_password_change','temporary_password_expires_at',
                'password_changed_at','auth_version','last_login_at','access_schedule_enabled','two_factor_required',
                'two_factor_secret','two_factor_recovery_codes','two_factor_confirmed_at','two_factor_last_step']);
        });
    }
}
