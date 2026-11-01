<?php
namespace Tests\Feature;

use App\Models\{User,CrmSede,Inscripcione,Persona};
use App\Services\{AccountSecurity,SedeAccess,UserTwoFactor};
use App\Support\SedeDB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Http,Queue};
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Permission;
use Tests\CrmTestCase;

class UserAccessTest extends CrmTestCase
{
    private $a,$b;
    private const SECRET='JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';
    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_10_31_000001_add_user_access_controls.php');
        (new \AddUserAccessControls)->up(); $this->app->forgetInstance(AccountSecurity::class);
        foreach(['Docente','Padre','Estudiante'] as $role) { \Spatie\Permission\Models\Role::firstOrCreate(['name'=>$role,'guard_name'=>'web']); }
        $this->a=CrmSede::firstOrCreate(['nombre'=>'IFE Central'],['activa'=>true]);
        $this->b=CrmSede::firstOrCreate(['nombre'=>'IFE Villa'],['activa'=>true]);
        foreach(['Listar Usuarios','Crear Usuarios','Editar Usuarios','Eliminar Usuarios','Editar Roles','Eliminar Roles'] as $name) { $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web'])); }
        User::withoutEvents(function(){ $this->operator->forceFill(['two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save(); });
        $this->operator->refresh();$this->withSession(['auth_version'=>0,'two_factor_verified'=>0]);
    }
    private function userAccount(string $role='Docente',?int $sede=null): User
    {
        $user=$this->actor($role);
        User::withoutEvents(fn()=>$user->forceFill(['password'=>Hash::make('Original!Password#72'),'force_password_change'=>false,'temporary_password_expires_at'=>null,'sede_id'=>$sede])->save());
        if($sede)$user->sedes()->sync([$sede]);return $user->fresh();
    }
    private function loginAs(User $user,?int $version=null): void { $this->actingAs($user);$this->withSession(['auth_version'=>$version??(int)$user->auth_version,'two_factor_verified'=>$user->two_factor_confirmed_at?(int)$user->auth_version:-1]); }
    private function scope(User $user,?int $sede=null): void
    {
        $request=Request::create('/');$request->setLaravelSession(session());session()->put('sede_activa',$sede);app(SedeAccess::class)->start($user,$request);
    }
    private function tagged(int $id,?int $sede): void
    {
        DB::table('inscripciones')->insert(['id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        if($sede)DB::table('sede_registros')->insert(['tabla'=>'inscripciones','registro_id'=>$id,'sede_id'=>$sede,'created_at'=>now()]);
    }
    public function test_migration_does_not_assign_or_rewrite_historical_business_records(): void
    {
        $this->assertNull($this->operator->sede_id);$this->assertFalse($this->operator->force_password_change);
        $this->assertSame(0,DB::table('sede_registros')->count());$this->assertSame(0,DB::table('persona_sede')->count());
    }
    public function test_admin_can_create_linked_user_with_roles_sucursales_and_one_time_temporary_credentials(): void
    {
        $person=$this->persona();DB::table('docentes')->insert(['persona_id'=>$person->id]);$count=Persona::count();
        $response=$this->post(route('users.store'),['name'=>'Docente nuevo','email'=>'new-local@example.test','persona_id'=>$person->id,'roles'=>['Docente'],'sede_id'=>$this->a->id,'sedes'=>[$this->b->id]])->assertOk()->assertViewIs('user.access.credentials');
        $user=User::where('email','new-local@example.test')->sole();$password=$response->viewData('password');
        $this->assertTrue(Hash::check($password,$user->password));$this->assertTrue($user->force_password_change);$this->assertTrue($user->hasRole('Docente'));
        $this->assertEqualsCanonicalizing([$this->a->id,$this->b->id],$user->sedes->pluck('id')->all());$this->assertSame($count,Persona::count());
        $this->assertStringNotContainsString($password,DB::table('user_access_audits')->get()->toJson());
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        $this->post(route('users.store'),['name'=>'Duplicado','email'=>'duplicate@example.test','persona_id'=>$person->id,'roles'=>['Docente'],'sede_id'=>$this->a->id])->assertSessionHasErrors('persona_id');
        $this->assertSame(1,User::where('persona_id',$person->id)->count());Http::assertNothingSent();Queue::assertNothingPushed();
    }
    public function test_staff_requires_main_sucursal_but_family_accounts_do_not(): void
    {
        $this->postJson(route('users.store'),['name'=>'Sin sucursal','email'=>'no-branch@example.test','roles'=>['Secretaria']])->assertUnprocessable()->assertJsonValidationErrors('sede_id');
        app(SedeAccess::class)->reset();$parent=$this->persona();$child=$this->persona();DB::table('persona_persona')->insert(['persona_id'=>$child->id,'persona_id_apoderado'=>$parent->id]);
        $this->post(route('users.store'),['name'=>'Cuenta familiar','email'=>'family@example.test','persona_id'=>$parent->id,'roles'=>['Padre']])->assertOk();
        $user=User::where('email','family@example.test')->sole();$this->assertNull($user->sede_id);$this->assertFalse($user->access_schedule_enabled);
    }
    public function test_non_admin_cannot_create_reset_block_or_reassign_users_even_with_an_edit_permission(): void
    {
        $user=$this->userAccount();$user->givePermissionTo(Permission::findByName('Editar Usuarios'));
        User::withoutEvents(fn()=>$user->forceFill(['two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());$this->loginAs($user->fresh());
        $this->postJson(route('users.access.password',$this->operator),['reason'=>'Intento indebido'])->assertForbidden();
        $this->putJson(route('users.access.state',$this->operator),['status'=>'blocked','reason'=>'Intento indebido'])->assertForbidden();
        $this->getJson(route('users.people',['q'=>'Lucas']))->assertForbidden();
        $this->postJson(route('users.access.reassign',$this->operator),['responsable_id'=>$user->id,'reason'=>'Intento indebido'])->assertForbidden();
    }
    public function test_temporary_password_blocks_normal_routes_until_changed_and_never_requires_periodic_changes(): void
    {
        $user=$this->userAccount('Padre');User::withoutEvents(fn()=>$user->forceFill(['force_password_change'=>true,'temporary_password_expires_at'=>now()->addDay()])->save());$this->loginAs($user);
        $this->getJson(route('profile.show'))->assertStatus(423)->assertJsonPath('redirect',route('security.password.show'));
        $this->put(route('security.password.update'),['current_password'=>'Original!Password#72','password'=>'Personal!Password#93','password_confirmation'=>'Personal!Password#93'])->assertRedirect(route('profile.show'));
        $this->assertFalse($user->fresh()->force_password_change);$this->assertTrue(Hash::check('Personal!Password#93',$user->fresh()->password));
        $this->get(route('profile.show'))->assertOk();$this->travel(700)->days();$this->get(route('profile.show'))->assertOk();$this->travelBack();
    }
    public function test_voluntary_change_verifies_current_password_and_revokes_other_sessions(): void
    {
        $user=$this->userAccount('Padre');$this->loginAs($user);
        $this->putJson(route('security.password.update'),['current_password'=>'Wrong!Password#72','password'=>'Personal!Password#93','password_confirmation'=>'Personal!Password#93'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame(0,(int)$user->fresh()->auth_version);
        $this->put(route('security.password.update'),['current_password'=>'Original!Password#72','password'=>'Personal!Password#93','password_confirmation'=>'Personal!Password#93'])->assertRedirect();
        $this->assertSame(1,(int)$user->fresh()->auth_version);
        $this->withSession(['auth_version'=>0]);$this->getJson(route('profile.show'))->assertUnauthorized();$this->assertGuest();
    }
    public function test_admin_reset_does_not_need_old_password_and_expired_temporary_password_is_rejected(): void
    {
        $user=$this->userAccount('Padre');$response=$this->post(route('users.access.password',$user),['reason'=>'Restablecimiento solicitado'])->assertOk();
        $this->assertTrue(Hash::check($response->viewData('password'),$user->fresh()->password));$this->assertTrue($user->fresh()->force_password_change);
        $this->loginAs($user->fresh());$this->travel(25)->hours();$this->getJson(route('profile.show'))->assertUnauthorized();$this->travelBack();
    }
    public function test_block_revokes_an_existing_session_and_preserves_the_user_and_audit(): void
    {
        $user=$this->userAccount();$this->put(route('users.access.state',$user),['status'=>'blocked','reason'=>'Baja laboral confirmada'])->assertRedirect();
        $this->assertSame('blocked',$user->fresh()->access_status);$this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$user->id,'actor_id'=>$this->operator->id,'event'=>'user_status_changed']);
        $this->loginAs($user->fresh(),0);$this->getJson(route('profile.show'))->assertUnauthorized();$this->assertNotNull(User::find($user->id));
    }
    public function test_last_active_admin_cannot_be_blocked_deleted_or_demoted(): void
    {
        $this->putJson(route('users.access.state',$this->operator),['status'=>'blocked','reason'=>'Prueba de protección'])->assertUnprocessable();
        $this->deleteJson(route('users.destroy',$this->operator),['reason'=>'Prueba de protección'])->assertUnprocessable();
        $role=\Spatie\Permission\Models\Role::firstOrCreate(['name'=>'Secretaria','guard_name'=>'web']);
        $this->putJson(route('rolusers.update',$this->operator),['roles'=>[$role->id],'reason'=>'Prueba de protección'])->assertUnprocessable();
        $this->assertTrue($this->operator->fresh()->hasRole('Admin'));$this->assertSame('active',$this->operator->fresh()->access_status);
    }
    public function test_weekly_windows_multiple_shifts_overnight_and_temporary_exceptions(): void
    {
        $user=$this->userAccount();User::withoutEvents(fn()=>$user->forceFill(['access_schedule_enabled'=>true])->save());
        DB::table('user_access_windows')->insert([['user_id'=>$user->id,'weekday'=>1,'starts_at'=>'08:00','ends_at'=>'12:00'],['user_id'=>$user->id,'weekday'=>1,'starts_at'=>'14:00','ends_at'=>'18:00'],['user_id'=>$user->id,'weekday'=>7,'starts_at'=>'22:00','ends_at'=>'02:00']]);
        $security=app(AccountSecurity::class);$this->assertTrue($security->allowedNow($user,Carbon::parse('2026-11-02 09:00','America/La_Paz')));$this->assertFalse($security->allowedNow($user,Carbon::parse('2026-11-02 13:00','America/La_Paz')));$this->assertTrue($security->allowedNow($user,Carbon::parse('2026-11-02 01:00','America/La_Paz')));
        DB::table('user_access_exceptions')->insert(['user_id'=>$user->id,'starts_at'=>'2026-11-02 12:00','ends_at'=>'2026-11-02 14:00','allowed'=>true,'reason'=>'Cobertura extraordinaria']);$this->assertTrue($security->allowedNow($user,Carbon::parse('2026-11-02 13:00','America/La_Paz')));
        DB::table('user_access_exceptions')->insert(['user_id'=>$user->id,'starts_at'=>'2026-11-02 12:30','ends_at'=>'2026-11-02 13:30','allowed'=>false,'reason'=>'Ausencia autorizada']);$this->assertFalse($security->allowedNow($user,Carbon::parse('2026-11-02 13:00','America/La_Paz')));
    }
    public function test_schedule_is_applied_to_a_session_already_logged_in(): void
    {
        $user=$this->userAccount('Padre');$this->loginAs($user);$this->get(route('profile.show'))->assertOk();
        User::withoutEvents(fn()=>$user->forceFill(['access_schedule_enabled'=>true])->save());$this->getJson(route('profile.show'))->assertUnauthorized();
    }
    public function test_branch_filter_includes_authorized_legacy_records_and_groups_search_or_conditions(): void
    {
        $user=$this->userAccount('Docente',$this->a->id);$this->tagged(501,$this->a->id);$this->tagged(502,$this->b->id);$this->tagged(503,null);$this->scope($user,$this->a->id);
        $this->assertEqualsCanonicalizing([501,503],Inscripcione::pluck('id')->all());
        $this->assertSame([],Inscripcione::where('id',502)->orWhere('id',999)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([501,503],SedeDB::table('inscripciones as e')->pluck('e.id')->all());
        $this->assertSame([],SedeDB::table('inscripciones as e')->where('e.id',502)->orWhere('e.id',999)->pluck('e.id')->all());
    }
    public function test_sucursal_switch_rejects_forged_ids_and_withdrawal_immediately_revokes_access(): void
    {
        $user=$this->userAccount('Docente',$this->a->id);$this->loginAs($user);
        $this->postJson(route('users.sede.switch'),['sede_id'=>$this->b->id])->assertForbidden();
        $user->sedes()->attach($this->b->id);$this->post(route('users.sede.switch'),['sede_id'=>$this->b->id])->assertRedirect();
        $this->assertEquals($this->b->id,session('sede_activa'));
        $user->sedes()->detach($this->b->id);$this->get(route('profile.show'))->assertOk();$this->assertEquals($this->a->id,session('sede_activa'));
    }
    public function test_admin_general_access_and_parent_real_child_access_do_not_use_staff_sucursal_rules(): void
    {
        $this->tagged(501,$this->a->id);$this->tagged(502,$this->b->id);$this->scope($this->operator,0);$this->assertCount(2,Inscripcione::all());app(SedeAccess::class)->reset();
        $own=$this->persona();$child=$this->persona();$other=$this->persona();$parent=$this->userAccount('Padre');$parent->persona_id=$own->id;$parent->save();DB::table('persona_persona')->insert(['persona_id'=>$child->id,'persona_id_apoderado'=>$own->id]);
        $student=DB::table('estudiantes')->where('persona_id',$child->id)->value('id');$unrelated=DB::table('estudiantes')->where('persona_id',$other->id)->value('id');DB::table('inscripciones')->where('id',501)->update(['estudiante_id'=>$student]);DB::table('inscripciones')->where('id',502)->update(['estudiante_id'=>$unrelated]);
        $this->scope($parent);$this->assertSame([501],Inscripcione::pluck('id')->all());$this->assertEqualsCanonicalizing([$own->id,$child->id],Persona::pluck('id')->all());
    }
    public function test_unauthenticated_internal_person_and_financial_apis_are_rejected(): void
    {
        $this->app['auth']->guard('web')->logout();$this->app['auth']->forgetGuards();$this->getJson('/api/administrativos')->assertUnauthorized();$this->getJson('/api/computaciones')->assertUnauthorized();
    }
    public function test_two_factor_gate_also_blocks_password_changes_before_challenge(): void
    {
        $this->withSession(['two_factor_verified'=>-1]);$this->getJson(route('profile.show'))->assertStatus(423)->assertJsonPath('redirect',route('security.two-factor.challenge'));
        $this->get(route('security.password.show'))->assertRedirect(route('security.two-factor.challenge'));
    }
    public function test_totp_is_encrypted_replay_protected_and_recovery_codes_are_single_use(): void
    {
        $user=$this->userAccount('Padre');$code=(new Google2FA)->oathTotp(self::SECRET,(int)floor(now()->timestamp/30));$twoFactor=app(UserTwoFactor::class);$result=$twoFactor->confirm($user,self::SECRET,$code);
        $raw=DB::table('users')->where('id',$user->id)->first();$this->assertStringNotContainsString(self::SECRET,$raw->two_factor_secret);$this->assertStringNotContainsString($result['codes'][0],$raw->two_factor_recovery_codes);
        $this->assertArrayNotHasKey('two_factor_secret',$result['user']->toArray());
        try{$twoFactor->verify($result['user'],$code);$this->fail('Accepted a replayed TOTP');}catch(ValidationException $e){$this->assertArrayHasKey('code',$e->errors());}
        $twoFactor->verify($result['user'],$result['codes'][0]);$this->assertCount(7,$user->fresh()->two_factor_recovery_codes);
        try{$twoFactor->verify($result['user'],$result['codes'][0]);$this->fail('Accepted a reused recovery code');}catch(ValidationException $e){$this->assertArrayHasKey('code',$e->errors());}
    }
    public function test_totp_enrollment_requires_password_and_confirmation_before_it_is_enabled(): void
    {
        $user=$this->userAccount('Padre');$this->loginAs($user);
        $this->postJson(route('security.two-factor.begin'),['current_password'=>'wrong'])->assertUnprocessable();
        $this->post(route('security.two-factor.begin'),['current_password'=>'Original!Password#72'])->assertRedirect();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);$pending=session('two_factor_pending');$secret=\Illuminate\Support\Facades\Crypt::decryptString($pending['secret']);
        $this->assertNotSame($secret,$pending['secret']);$code=(new Google2FA)->oathTotp($secret,(int)floor(now()->timestamp/30));
        $this->post(route('security.two-factor.confirm'),['code'=>$code])->assertOk()->assertViewIs('user.security.recovery');$this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->get(route('profile.show'))->assertOk();
    }
    public function test_admin_pages_render_existing_roles_sucursales_and_access_history(): void
    {
        $user=$this->userAccount();
        $this->get(route('users.index'))->assertOk()->assertSee('Administración de usuarios');
        $this->get(route('users.create'))->assertOk()->assertSee('Creación rápida')->assertSee('Sucursal principal');
        $this->get(route('users.edit',$user))->assertOk()->assertSee('Motivo del cambio');
        $this->get(route('users.access.edit',$user))->assertOk()->assertSee('America/La_Paz')->assertSee('Historial de administración');
    }
    public function test_new_records_are_attributed_atomically_and_keep_their_original_sucursal(): void
    {
        $person=$this->persona();$student=DB::table('estudiantes')->where('persona_id',$person->id)->value('id');$user=$this->userAccount('Docente',$this->a->id);$this->scope($user,$this->a->id);
        $record=Inscripcione::create(['estudiante_id'=>$student]);
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','registro_id'=>$record->id,'sede_id'=>$this->a->id]);
        $user->sedes()->attach($this->b->id);$this->scope($user,$this->b->id);
        try{$record->forceFill(['updated_at'=>now()->addMinute()])->save();$this->fail('Updated another branch record');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','registro_id'=>$record->id,'sede_id'=>$this->a->id]);
    }
    public function test_forged_parent_ids_and_creating_without_a_sucursal_are_rejected_before_insertion(): void
    {
        $person=$this->persona();$student=DB::table('estudiantes')->where('persona_id',$person->id)->value('id');DB::table('persona_sede')->insert(['persona_id'=>$person->id,'sede_id'=>$this->b->id]);
        $user=$this->userAccount('Docente',$this->a->id);$this->scope($user,$this->a->id);$before=DB::table('inscripciones')->count();
        try{Inscripcione::create(['estudiante_id'=>$student]);$this->fail('Accepted foreign student');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        $this->assertSame($before,DB::table('inscripciones')->count());$this->assertSame(0,DB::table('sede_registros')->count());
        $this->scope($this->operator,0);try{Inscripcione::create(['estudiante_id'=>$student]);$this->fail('Accepted no working branch');}catch(ValidationException $e){$this->assertArrayHasKey('sede_id',$e->errors());}
    }
    public function test_branch_protection_also_applies_to_joined_reports_and_bulk_updates(): void
    {
        $this->tagged(501,$this->a->id);$this->tagged(502,$this->b->id);$user=$this->userAccount('Docente',$this->a->id);$this->scope($user,$this->a->id);
        $rows=SedeDB::table('inscripciones as a')->join('inscripciones as b','b.id','=','a.id')->pluck('b.id')->all();$this->assertSame([501],$rows);
        $count=SedeDB::table('inscripciones')->where('id',502)->orWhere('id',501)->update(['updated_at'=>'2026-11-01 12:00:00']);$this->assertSame(1,$count);
        $this->assertNotSame('2026-11-01 12:00:00',DB::table('inscripciones')->where('id',502)->value('updated_at'));
    }
    public function test_schedule_save_and_exceptions_validate_dates_and_keep_an_unrestricted_admin(): void
    {
        $user=$this->userAccount();
        $this->put(route('users.access.schedule',$user),['enabled'=>1,'reason'=>'Nuevo turno de trabajo','windows'=>[['weekday'=>1,'starts_at'=>'08:00','ends_at'=>'12:00'],['weekday'=>1,'starts_at'=>'14:00','ends_at'=>'18:00']],'exceptions'=>[['starts_at'=>'2026-11-02T08:00','ends_at'=>'2026-11-02T10:00','allowed'=>0,'reason'=>'Permiso temporal']]])->assertRedirect();
        $this->assertSame(2,DB::table('user_access_windows')->where('user_id',$user->id)->count());$this->assertSame(1,DB::table('user_access_exceptions')->where('user_id',$user->id)->count());
        $this->putJson(route('users.access.schedule',$this->operator),['enabled'=>1,'reason'=>'Prueba protección horario','windows'=>[['weekday'=>1,'starts_at'=>'08:00','ends_at'=>'12:00']]])->assertUnprocessable();
    }
    public function test_crm_still_records_activities_and_analytics_with_branch_access_enabled(): void
    {
        $lead=$this->lead(['crm_sede_id'=>$this->a->id]);$this->withSession(['sede_activa'=>$this->a->id]);
        $this->get(route('crm.tablero'))->assertOk();
        $this->post(route('crm.actividad',$lead),['tipo'=>'llamada','observacion'=>'Seguimiento de prueba local','version'=>$lead->crm_version])->assertRedirect();
    }
    public function test_reused_queries_exists_relations_and_catalog_root_joins_cannot_bypass_branch_access(): void
    {
        $person=$this->persona();$student=DB::table('estudiantes')->where('persona_id',$person->id)->value('id');
        $this->tagged(501,$this->a->id);$this->tagged(502,$this->b->id);DB::table('inscripciones')->update(['estudiante_id'=>$student,'modalidad_id'=>1]);DB::table('modalidads')->insert(['id'=>1,'modalidad'=>'Prueba']);
        $user=$this->userAccount('Docente',$this->a->id);$this->scope($user,$this->a->id);
        $query=SedeDB::table('inscripciones')->where('id',501);$this->assertSame([501],$query->pluck('id')->all());
        $query->orWhere('id',502);$this->assertSame([501],$query->pluck('id')->all());
        $this->assertSame([501],\App\Models\Modalidad::join('inscripciones as e','e.modalidad_id','=','modalidads.id')->pluck('e.id')->all());
        $this->assertSame([$student],\App\Models\Estudiante::whereHas('inscripciones',fn($q)=>$q->where('id',501))->pluck('id')->all());
        $this->assertSame([],\App\Models\Estudiante::whereHas('inscripciones',fn($q)=>$q->where('id',502))->pluck('id')->all());
        $this->assertSame([$person->id],SedeDB::table('personas')->whereExists(fn($q)=>$q->selectRaw('1')->from('inscripciones')->where('id',501))->pluck('id')->all());
        $this->assertSame([],SedeDB::table('personas')->whereExists(fn($q)=>$q->selectRaw('1')->from('inscripciones')->where('id',502))->pluck('id')->all());
    }
    public function test_financial_subqueries_include_only_payments_in_the_active_branch(): void
    {
        \Illuminate\Support\Facades\Schema::create('pagos',function($t){$t->id();$t->decimal('monto');$t->string('pagable_type');$t->unsignedBigInteger('pagable_id');$t->timestamps();});
        $this->tagged(501,null);
        DB::table('pagos')->insert([['id'=>1,'monto'=>25,'pagable_type'=>Inscripcione::class,'pagable_id'=>501],['id'=>2,'monto'=>90,'pagable_type'=>Inscripcione::class,'pagable_id'=>501]]);
        foreach([1=>$this->a->id,2=>$this->b->id] as $id=>$sede)DB::table('sede_registros')->insert(['tabla'=>'pagos','registro_id'=>$id,'sede_id'=>$sede,'created_at'=>now()]);
        $this->scope($this->userAccount('Docente',$this->a->id),$this->a->id);
        $rows=Inscripcione::select(['id','acuenta'=>SedeAccess::aggregate('pagos','SUM(monto)','pagable_id','inscripciones.id')->where('pagable_type',Inscripcione::class)])->get();
        $this->assertSame(25,(int)$rows->sole()->acuenta);
        $this->assertSame([501],Inscripcione::where(SedeAccess::aggregate('pagos','SUM(monto)','pagable_id','inscripciones.id'),'<',50)->pluck('id')->all());
    }
    public function test_schedule_exceptions_are_normalized_and_denials_work_without_a_weekly_schedule(): void
    {
        $user=$this->userAccount();$this->travelTo(Carbon::parse('2026-11-02 09:00','America/La_Paz'));
        $this->put(route('users.access.schedule',$user),['enabled'=>0,'reason'=>'Permiso laboral temporal','exceptions'=>[['starts_at'=>'2026-11-02T08:00','ends_at'=>'2026-11-02T10:00','allowed'=>0,'reason'=>'Ausencia']]])->assertRedirect();
        $this->assertDatabaseHas('user_access_exceptions',['user_id'=>$user->id,'starts_at'=>'2026-11-02 08:00:00']);
        $this->assertFalse(app(AccountSecurity::class)->allowedNow($user->fresh()));
        $this->travelTo(Carbon::parse('2026-11-02 10:00','America/La_Paz'));$this->assertTrue(app(AccountSecurity::class)->allowedNow($user->fresh()));
        $this->putJson(route('users.access.schedule',$this->operator),['enabled'=>0,'reason'=>'Proteccion administrador','exceptions'=>[['starts_at'=>'2026-11-02T11:00','ends_at'=>'2026-11-02T12:00','allowed'=>0,'reason'=>'No disponible']]])->assertUnprocessable();
        $this->travelBack();
    }
    public function test_real_login_rejects_blocked_users_and_requires_admin_second_factor(): void
    {
        $user=$this->userAccount('Padre');User::withoutEvents(fn()=>$user->forceFill(['access_status'=>'blocked'])->save());
        $this->app['auth']->guard('web')->logout();$this->app['auth']->forgetGuards();
        $this->postJson('/login',['email'=>$user->email,'password'=>'Original!Password#72'])->assertUnprocessable();$this->assertGuest();
        User::withoutEvents(fn()=>$this->operator->forceFill(['password'=>Hash::make('Original!Password#72')])->save());
        $this->post('/login',['email'=>$this->operator->email,'password'=>'Original!Password#72'])->assertRedirect();
        $this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $code=(new Google2FA)->oathTotp(self::SECRET,(int)floor(now()->timestamp/30));
        $this->post(route('security.two-factor.verify'),['code'=>$code])->assertRedirect(route('profile.show'));
        $this->get(route('profile.show'))->assertOk();$this->assertNotNull($this->operator->fresh()->last_login_at);
    }
    public function test_family_accounts_cannot_access_operations_and_staff_apis_still_require_permissions(): void
    {
        $family=$this->userAccount('Padre');$this->loginAs($family);
        $this->getJson('/api/administrativos')->assertForbidden();$this->getJson(route('users.index'))->assertForbidden();$this->getJson('/api/estados')->assertOk();
        $staff=$this->userAccount('Docente',$this->a->id);$this->loginAs($staff);$this->getJson('/api/administrativos')->assertForbidden();
        $this->postJson('/api/send-message',[])->assertOk();
    }
    public function test_photo_access_and_user_lists_respect_branch_assignments(): void
    {
        foreach(['one.jpg','two.jpg'] as $name){$image=\Illuminate\Http\UploadedFile::fake()->image($name);\Illuminate\Support\Facades\Storage::disk('public')->put('estudiantes/'.$name,file_get_contents($image->getPathname()));}
        $one=$this->persona(['foto'=>'estudiantes/one.jpg']);$two=$this->persona(['foto'=>'estudiantes/two.jpg']);
        foreach([[$one->id,$this->a->id],[$two->id,$this->b->id]] as [$id,$sede])DB::table('persona_sede')->insert(['persona_id'=>$id,'sede_id'=>$sede]);
        $user=$this->userAccount('Docente',$this->a->id);$other=$this->userAccount('Docente',$this->b->id);$this->loginAs($user);
        $photos=app(\App\Services\ProfilePhotoService::class);$this->get($photos->url($one->foto))->assertOk();$this->get($photos->url($two->foto))->assertForbidden();
        $this->get('/storage/'.$one->foto)->assertOk();$this->get('/storage/'.$two->foto)->assertForbidden();
        $this->scope($user,$this->a->id);$this->assertFalse(User::where('id',$other->id)->exists());$this->assertTrue(User::where('id',$user->id)->exists());
        $this->app['auth']->guard('web')->logout();$this->app['auth']->forgetGuards();$this->getJson('/storage/'.$one->foto)->assertUnauthorized();
    }
    public function test_morph_references_reject_security_records_and_accept_authorized_payroll_periods(): void
    {
        \Illuminate\Support\Facades\Schema::create('periodables',function($t){$t->id();$t->unsignedBigInteger('periodable_id');$t->string('periodable_type');$t->timestamps();});
        \Illuminate\Support\Facades\Schema::create('pagos',function($t){$t->id();$t->decimal('monto');$t->string('pagable_type');$t->unsignedBigInteger('pagable_id');$t->timestamps();});
        $person=$this->persona();$teacher=DB::table('docentes')->insertGetId(['persona_id'=>$person->id]);
        $this->scope($this->userAccount('Docente',$this->a->id),$this->a->id);
        $period=new \App\Models\Periodable;$period->forceFill(['periodable_id'=>$teacher,'periodable_type'=>\App\Models\Docente::class])->save();
        $payment=\App\Models\Pago::create(['monto'=>100,'pagable_id'=>$period->id,'pagable_type'=>\App\Models\Periodable::class]);
        $this->assertDatabaseHas('sede_registros',['tabla'=>'pagos','registro_id'=>$payment->id,'sede_id'=>$this->a->id]);
        try{app(SedeAccess::class)->authorizeReference(User::class,$this->operator->id);$this->fail('Accepted a security model');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(422,$e->getStatusCode());}
    }
    public function test_branch_reassignment_is_audited_and_revokes_sessions_without_rewriting_enrollments(): void
    {
        $person=$this->persona();DB::table('docentes')->insert(['persona_id'=>$person->id]);$user=$this->userAccount('Docente',$this->a->id);$user->persona_id=$person->id;$user->save();$this->tagged(501,$this->a->id);
        $this->patch(route('users.update',$user),['name'=>$user->name,'email'=>$user->email,'persona_id'=>$person->id,'roles'=>['Docente'],'sede_id'=>$this->b->id,'sedes'=>[],'reason'=>'Traslado de sucursal confirmado'])->assertRedirect(route('users.index'));
        $this->assertSame([$this->b->id],$user->fresh()->sedes->pluck('id')->all());$this->assertSame(1,(int)$user->fresh()->auth_version);
        $this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$user->id,'actor_id'=>$this->operator->id,'event'=>'user_access_updated']);
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','registro_id'=>501,'sede_id'=>$this->a->id]);
        $this->loginAs($user->fresh(),0);$this->getJson(route('profile.show'))->assertUnauthorized();
    }
    public function test_suspension_expiry_reactivation_and_lost_second_factor_require_an_authorized_admin(): void
    {
        $user=$this->userAccount('Padre');$this->put(route('users.access.state',$user),['status'=>'suspended','suspended_until'=>now()->addHour()->format('Y-m-d H:i:s'),'reason'=>'Suspension temporal autorizada'])->assertRedirect();
        $this->assertNotNull(app(AccountSecurity::class)->rejection($user->fresh()));$this->travel(61)->minutes();$this->assertNull(app(AccountSecurity::class)->rejection($user->fresh()));$this->travelBack();
        $this->put(route('users.access.state',$user),['status'=>'active','reason'=>'Reactivacion de cuenta autorizada'])->assertRedirect();$this->assertSame('active',$user->fresh()->access_status);$this->assertNull($user->fresh()->suspended_until);
        User::withoutEvents(fn()=>$user->forceFill(['two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());
        $this->deleteJson(route('users.access.two-factor',$user),['current_password'=>'incorrect','reason'=>'Recuperacion autorizada'])->assertUnprocessable();$this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        User::withoutEvents(fn()=>$this->operator->forceFill(['password'=>Hash::make('Admin!Password#72')])->save());
        $this->delete(route('users.access.two-factor',$user),['current_password'=>'Admin!Password#72','reason'=>'Recuperacion autorizada'])->assertRedirect();$this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$user->id,'event'=>'two_factor_reset']);
    }
    public function test_crm_reassignment_preserves_original_owner_and_attributes_the_new_action(): void
    {
        $target=$this->userAccount('Secretaria',$this->a->id);$lead=$this->lead(['crm_sede_id'=>$this->a->id]);$original=$lead->responsable_inicial_id;
        $this->post(route('users.access.reassign',$this->operator),['responsable_id'=>$target->id,'reason'=>'Cobertura por traslado laboral'])->assertRedirect();
        $this->assertEquals($target->id,$lead->fresh()->responsable_id);$this->assertEquals($original,$lead->fresh()->responsable_inicial_id);
        $this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$this->operator->id,'actor_id'=>$this->operator->id,'event'=>'pending_reassigned']);
        $this->assertDatabaseHas('crm_actividad_detalles',['tipo'=>'reasignacion','actor_id'=>$this->operator->id]);
        $event=\App\Models\CrmActividadDetalle::where('tipo','reasignacion')->sole();
        foreach(['observacions','crm_actividad_detalles'] as $table)$this->assertDatabaseHas('sede_registros',['tabla'=>$table,'registro_id'=>$event->observacion_id,'sede_id'=>$this->a->id]);
    }
    public function test_family_can_read_teachers_and_rooms_of_actual_classes_without_gaining_other_students(): void
    {
        \Illuminate\Support\Facades\Schema::create('programacions',function($t){$t->id();$t->unsignedBigInteger('inscripcione_id');$t->unsignedBigInteger('docente_id');$t->unsignedBigInteger('aula_id');});
        \Illuminate\Support\Facades\Schema::create('aulas',function($t){$t->id();$t->string('aula');});
        $person=$this->persona();$child=$this->persona();$teacherPerson=$this->persona();$parent=$this->userAccount('Padre');$parent->persona_id=$person->id;$parent->save();
        DB::table('persona_persona')->insert(['persona_id'=>$child->id,'persona_id_apoderado'=>$person->id]);$teacher=DB::table('docentes')->insertGetId(['persona_id'=>$teacherPerson->id]);
        foreach([[$child,501],[$teacherPerson,502]] as [$p,$id])DB::table('inscripciones')->insert(['id'=>$id,'estudiante_id'=>$p->estudiante->id]);
        DB::table('aulas')->insert([['id'=>1,'aula'=>'Aula vinculada'],['id'=>2,'aula'=>'Otra aula']]);DB::table('programacions')->insert(['inscripcione_id'=>501,'docente_id'=>$teacher,'aula_id'=>1]);
        $request=Request::create('/padre/home');$request->setLaravelSession(session());$route=new \Illuminate\Routing\Route('GET','padre/home',fn()=>null);$route->name('padre.home');$request->setRouteResolver(fn()=>$route);app(SedeAccess::class)->start($parent,$request);
        $this->assertTrue(Persona::where('id',$teacherPerson->id)->exists());$this->assertSame([501],Inscripcione::pluck('id')->all());$this->assertSame([1],\App\Models\Aula::pluck('id')->all());
    }
    public function test_existing_role_editor_invalidates_permission_cache_and_revokes_affected_sessions(): void
    {
        $user=$this->userAccount('Docente',$this->a->id);$role=\Spatie\Permission\Models\Role::findByName('Docente');$permission=Permission::findByName('Listar Personas');
        $user->can('Listar Personas');
        $this->put(route('role.update',$role),['name'=>'Docente','permissions'=>[$permission->id]])->assertRedirect(route('role.index'));
        $this->assertTrue($user->fresh()->can('Listar Personas'));$this->assertSame(1,(int)$user->fresh()->auth_version);
        $this->assertDatabaseHas('user_access_audits',['subject_user_id'=>$user->id,'actor_id'=>$this->operator->id,'event'=>'role_permissions_changed']);
        $adminRole=\Spatie\Permission\Models\Role::findByName('Admin');$this->deleteJson(route('role.destroy',$adminRole))->assertUnprocessable();
    }
    public function test_admin_can_log_in_again_after_enrolling_two_factor_and_changing_password(): void
    {
        $old='Previous!Password#72';$new='Replacement!Password#93';
        User::withoutEvents(fn()=>$this->operator->forceFill(['password'=>Hash::make($old),'force_password_change'=>false,'two_factor_secret'=>null,'two_factor_confirmed_at'=>null,'two_factor_last_step'=>null,'two_factor_recovery_codes'=>null])->save());
        $this->loginAs($this->operator->fresh());
        $this->get(route('profile.show'))->assertRedirect(route('security.two-factor.show'));
        $this->post(route('security.two-factor.begin'),['current_password'=>$old])->assertRedirect();
        $secret=\Illuminate\Support\Facades\Crypt::decryptString(session('two_factor_pending')['secret']);
        $code=(new Google2FA)->oathTotp($secret,(int)floor(now()->timestamp/30));
        $this->post(route('security.two-factor.confirm'),['code'=>$code])->assertOk();
        // Enrollment verifies the current password and must never change it.
        $this->assertTrue(Hash::check($old,$this->operator->fresh()->password));$this->assertNull($this->operator->fresh()->password_changed_at);
        $this->put(route('security.password.update'),['current_password'=>$old,'password'=>$new,'password_confirmation'=>$new])->assertRedirect(route('profile.show'));
        $this->assertTrue(Hash::check($new,$this->operator->fresh()->password));
        $this->post(route('logout'))->assertRedirect();$this->assertGuest();
        $this->get(route('login'))->assertOk();
        $this->postJson('/login',['email'=>$this->operator->email,'password'=>$old])->assertUnprocessable();$this->assertGuest();
        $this->post('/login',['email'=>$this->operator->email,'password'=>$new])->assertRedirect('/home');
        $this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->travel(31)->seconds();$code=(new Google2FA)->oathTotp($secret,(int)floor(now()->timestamp/30));
        $this->post(route('security.two-factor.verify'),['code'=>$code])->assertRedirect(route('profile.show'));
        $this->get(route('profile.show'))->assertOk();$this->travelBack();
    }
    public function test_legacy_family_credential_action_requires_admin_and_uses_atomic_temporary_resets(): void
    {
        $student=$this->persona(['telefono'=>'70000001']);$parent=$this->persona(['telefono'=>'70000002']);DB::table('persona_persona')->insert(['persona_id'=>$student->id,'persona_id_apoderado'=>$parent->id]);
        $studentUser=$this->userAccount('Estudiante');$studentUser->persona_id=$student->id;$studentUser->save();$parentUser=$this->userAccount('Padre');$parentUser->persona_id=$parent->id;$parentUser->save();
        $this->partialMock(\App\Http\Middleware\VerifyCsrfToken::class,function($mock){$mock->shouldAllowMockingProtectedMethods()->shouldReceive('runningUnitTests')->andReturn(false);});
        $token=str_repeat('c',40);$this->withSession(['_token'=>$token]);
        $staff=$this->userAccount('Secretaria',$this->a->id);User::withoutEvents(fn()=>$staff->forceFill(['two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());$this->loginAs($staff->fresh());
        $this->postJson(route('persona.enviar.credenciales.whatsapp',$student),['_token'=>$token])->assertForbidden();$this->assertSame(0,(int)$studentUser->fresh()->auth_version);
        $this->loginAs($this->operator->fresh());$this->postJson(route('persona.enviar.credenciales.whatsapp',$student))->assertStatus(419);$this->assertSame(0,(int)$studentUser->fresh()->auth_version);
        $response=$this->postJson(route('persona.enviar.credenciales.whatsapp',$student),['_token'=>$token])->assertOk();
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        foreach([$studentUser,$parentUser] as $user){$this->assertTrue($user->fresh()->force_password_change);$this->assertSame(1,(int)$user->fresh()->auth_version);$this->assertNotNull($user->fresh()->temporary_password_expires_at);}
        $this->getJson(route('persona.enviar.credenciales.whatsapp',$student))->assertStatus(405);Http::assertNothingSent();
    }
}
