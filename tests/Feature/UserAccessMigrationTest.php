<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\CredencialesPadreNotification;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use Tests\CrmTestCase;
class UserAccessMigrationTest extends CrmTestCase
{
    public function test_migration_removes_plaintext_notification_passwords_and_only_flags_still_exposed_credentials(): void
    {
        $exposed=$this->actor('Padre');$rotated=$this->actor('Padre');$original=$exposed->password;
        foreach([[$exposed,'testing-only'],[$rotated,'old-nonmatching-password']] as [$user,$password]){
            DB::table('notifications')->insert(['id'=>(string)Str::uuid(),'type'=>CredencialesPadreNotification::class,'notifiable_type'=>User::class,'notifiable_id'=>$this->operator->id,
                'data'=>json_encode(['usuario_id'=>$user->id,'password'=>$password,'mensaje'=>'Conservar historial']),'created_at'=>now(),'updated_at'=>now()]);
        }
        require_once database_path('migrations/2026_10_31_000001_add_user_access_controls.php');(new \AddUserAccessControls)->up();
        $this->assertTrue($exposed->fresh()->force_password_change);$this->assertFalse($rotated->fresh()->force_password_change);
        $this->assertSame($original,$exposed->fresh()->password);$this->assertSame(1,(int)$exposed->fresh()->auth_version);
        foreach(DB::table('notifications')->get() as $notification){$data=json_decode($notification->data,true);$this->assertArrayNotHasKey('password',$data);$this->assertSame('Conservar historial',$data['mensaje']);}
        $this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$exposed->id,'event'=>'legacy_credential_exposure']);
        $this->assertSame(0,DB::table('sede_registros')->count());
    }
    public function test_new_credential_notifications_store_instructions_without_the_plaintext_secret(): void
    {
        $person=$this->persona();$notification=new CredencialesPadreNotification($this->operator,'DoNotStore!Temporary#84',$person);
        $data=$notification->toArray($this->operator);$this->assertArrayNotHasKey('password',$data);
        $this->assertStringNotContainsString('DoNotStore!Temporary#84',json_encode($data));$this->assertTrue($data['requiere_entrega_segura']);
    }
}
