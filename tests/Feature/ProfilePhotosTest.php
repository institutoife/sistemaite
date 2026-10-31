<?php
namespace Tests\Feature;

use App\Exceptions\GoogleContactsException;
use App\Models\{Persona,User};
use App\Services\{ProfilePhotoService,GoogleContactProfile};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Http,Queue,Schema,Storage};
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\CrmTestCase;

class ProfilePhotosTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $script=file_get_contents(public_path('assets/js/phone-verification.js'));
        Storage::disk('public')->put('_web/assets/js/phone-verification.js',$script);
        $this->app->instance('path.public',Storage::disk('public')->path('_web'));
        foreach (['Listar Usuarios','Editar Usuarios','Listar Docentes'] as $name) {
            $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']));
        }
    }

    private function image(string $path='estudiantes/5300.jpg'): string
    {
        $file=UploadedFile::fake()->image('test.jpg',60,30);
        Storage::disk('public')->put($path,file_get_contents($file->getPathname()));
        return $path;
    }

    public function test_lists_and_detail_use_the_same_existing_photo_for_a_legacy_person_without_a_storage_link(): void
    {
        $this->image('_web/storage/estudiantes/5300.jpg');
        $person=$this->persona(['id'=>5300,'foto'=>'storage/estudiantes/5300.jpg']);
        $before=$person->fresh()->getAttributes(); $photos=app(ProfilePhotoService::class);
        $url=$photos->url($person->foto);
        $this->assertNotSame($photos->placeholder(),$url);
        $this->getJson(route('persona.ajax',['draw'=>1,'length'=>10]))->assertOk()->assertJsonPath('data.0.id',5300)->assertJsonPath('data.0.foto_url',$url);
        $this->get($url)->assertOk()->assertHeader('Content-Type','image/jpeg')->assertHeader('X-Content-Type-Options','nosniff');
        $this->assertSame($before,$person->fresh()->getAttributes());
        Storage::disk('public')->assertMissing('estudiantes/5300.jpg'); Http::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_known_path_aliases_and_a_local_absolute_url_resolve_the_exact_image(): void
    {
        $this->image('_web/foto/estudiantes/old.jpg'); $photos=app(ProfilePhotoService::class);
        foreach (['estudiantes/old.jpg','storage/estudiantes/old.jpg','/storage/estudiantes/old.jpg',
            'storage/app/public/estudiantes/old.jpg','public/storage/estudiantes/old.jpg','foto/estudiantes/old.jpg',
            'public/foto/estudiantes/old.jpg','estudiantes\\old.jpg',url('/storage/estudiantes/old.jpg')] as $path) {
            $file=$photos->file($path); $this->assertSame('public/foto',$file['location']);
            $this->get($photos->url($path))->assertOk()->assertHeader('Content-Type','image/jpeg');
        }
        $canonical=$this->image('estudiantes/old.jpg');
        $this->assertSame(realpath(Storage::disk('public')->path($canonical)),$photos->file('estudiantes/old.jpg')['path']);
        $this->assertSame($photos->placeholder(),$photos->url('unrelated/old.jpg')); Http::assertNothingSent();
    }

    public function test_missing_defaults_and_invalid_files_use_a_shared_placeholder_and_cannot_serve_arbitrary_files(): void
    {
        $photos=app(ProfilePhotoService::class);
        Storage::disk('public')->put('estudiantes/script.jpg','<?php echo "private";');
        foreach ([null,'','estudiantes/sinfoto.jpg','estudiantes/sinperfil.png','estudiantes/missing.jpg',
            '../.env','storage/../secret.jpg','/etc/passwd','estudiantes/script.jpg','data:image/svg+xml,unsafe',"test\0.jpg"] as $path) {
            $this->assertSame($photos->placeholder(),$photos->url($path));
        }
        $this->get(route('foto.show',['filename'=>'estudiantes/script.jpg']))->assertNotFound();
        $this->get(route('foto.show',['filename'=>'estudiantes/missing.jpg']))->assertNotFound();
        $this->app['auth']->guard('web')->logout(); $this->app['auth']->forgetGuards();
        $this->getJson(route('foto.show',['filename'=>'estudiantes/script.jpg']))->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_login_avatar_and_user_listing_fall_back_to_the_linked_person_instead_of_a_default_or_lost_file(): void
    {
        $photos=app(ProfilePhotoService::class); $photo=$this->image(); $person=$this->persona(['foto'=>$photo]);
        $user=$this->actor('Padre'); $user->persona_id=$person->id; $user->foto='estudiantes/sinperfil.png'; $user->save();
        $url=$photos->url($photo);
        $this->assertSame($url,$user->adminlte_image());
        $user->foto='usuarios/lost.jpg'; $user->save();
        $this->assertSame($url,$user->fresh()->adminlte_image());
        $this->getJson(route('users.data',['length'=>100]))->assertOk()->assertJsonFragment(['id'=>$user->id,'foto_url'=>$url]);
        $this->get(route('users.show',$user))->assertOk()->assertSee($url,false)->assertSee('data-profile-photo',false);
        $unlinked=$this->actor('Padre'); $this->assertSame($photos->placeholder(),$unlinked->adminlte_image());
        Http::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_photo_lists_keep_the_permissions_of_their_modules(): void
    {
        $this->actingAs($this->actor('Padre'));
        $this->getJson(route('persona.ajax'))->assertForbidden();
        $this->getJson(route('users.data'))->assertForbidden();
        $this->getJson(url('listar/docentes'))->assertForbidden();
    }

    public function test_teacher_list_returns_a_resolved_photo_url_without_changing_the_saved_path(): void
    {
        Schema::create('mododocentes',function ($table) { $table->id(); $table->string('mododocente'); });
        Schema::table('docentes',function ($table) { $table->unsignedBigInteger('mododocente_id')->nullable(); $table->string('perfil')->nullable(); });
        DB::table('mododocentes')->insert(['id'=>1,'mododocente'=>'Prueba']);
        $photo=$this->image('_web/foto/docentes/own.jpg'); $person=$this->persona(['foto'=>'docentes/own.jpg']);
        DB::table('docentes')->insert(['persona_id'=>$person->id,'mododocente_id'=>1]);
        $this->getJson(url('listar/docentes'))->assertOk()->assertJsonPath('data.0.persona_id',$person->id)
            ->assertJsonPath('data.0.foto_url',app(ProfilePhotoService::class)->url($person->foto));
        $this->assertSame('docentes/own.jpg',$person->fresh()->foto); Http::assertNothingSent();
    }

    public function test_replacing_a_person_photo_updates_the_linked_login_and_preserves_old_files_and_rollback(): void
    {
        $old=$this->image(); $new=$this->image('estudiantes/new.jpg'); $person=$this->persona(['foto'=>$old]);
        $user=$this->actor('Padre'); $user->persona_id=$person->id; $user->foto=$old; $user->save();
        $person->foto=$new; $person->save(); $this->assertSame($new,$user->fresh()->foto);
        try { DB::transaction(function () use ($person,$old) { $person->foto=$old; $person->save(); throw new \RuntimeException('Rollback photo'); }); }
        catch (\RuntimeException $exception) { $this->assertSame('Rollback photo',$exception->getMessage()); }
        $this->assertSame($new,$person->fresh()->foto); $this->assertSame($new,$user->fresh()->foto);
        Storage::disk('public')->assertExists($old); Storage::disk('public')->assertExists($new); Http::assertNothingSent();
    }

    public function test_camera_saves_for_a_person_without_a_login_and_an_empty_upload_preserves_the_photo(): void
    {
        $person=$this->persona(); $file=UploadedFile::fake()->image('camera.png',800,400);
        $data='data:image/png;base64,'.base64_encode(file_get_contents($file->getPathname()));
        $this->put(route('guardarfoto',$person),['imagen'=>$data])->assertSessionHasNoErrors()->assertRedirect(route('telefonos.crear',$person));
        $photo=$person->fresh()->foto; $info=getimagesize(Storage::disk('public')->path($photo));
        $this->assertSame([800,400],[$info[0],$info[1]]); $this->assertSame('image/jpeg',$info['mime']);
        $this->put(route('guardarfotojpg',$person),[])->assertRedirect();
        $this->assertSame($photo,$person->fresh()->foto);
        $this->putJson(route('guardarfoto',$person),['imagen'=>'data:image/png;base64,not-an-image'])->assertUnprocessable()->assertJsonValidationErrors('imagen');
        $this->assertSame($photo,$person->fresh()->foto); Http::assertNothingSent();
    }

    public function test_new_uploads_retain_aspect_ratio_detail_and_small_photos_are_not_upscaled(): void
    {
        $photos=app(ProfilePhotoService::class);
        foreach ([[1800,900,1600,800],[80,40,80,40]] as [$width,$height,$expectedWidth,$expectedHeight]) {
            $name=$photos->store(UploadedFile::fake()->image('wide.png',$width,$height));
            $info=getimagesize(Storage::disk('public')->path($name));
            $this->assertSame([$expectedWidth,$expectedHeight],[$info[0],$info[1]]); $this->assertSame('image/jpeg',$info['mime']);
        }
        foreach ([UploadedFile::fake()->create('bad.html',1,'text/html'),UploadedFile::fake()->image('huge.jpg',4001,10)] as $invalid) {
            try { $photos->store($invalid); $this->fail('Se aceptó un archivo inválido.'); }
            catch (ValidationException $exception) { $this->assertArrayHasKey('foto',$exception->errors()); }
        }
        Http::assertNothingSent();
    }

    public function test_updating_a_user_photo_keeps_an_old_file_still_referenced_by_persona(): void
    {
        $old=$this->image(); $person=$this->persona(['foto'=>$old]);
        $user=$this->actor('Padre'); $user->persona_id=$person->id; $user->foto=$old; $user->save();
        $this->patch(route('users.update',$user),['name'=>'Cuenta de prueba','email'=>$user->email,
            'foto'=>UploadedFile::fake()->image('new.png',1200,600)])->assertSessionHasNoErrors()->assertRedirect();
        Storage::disk('public')->assertExists($old); $this->assertSame($old,$person->fresh()->foto);
        $this->assertNotSame($old,$user->fresh()->foto);
        $info=getimagesize(Storage::disk('public')->path($user->fresh()->foto)); $this->assertSame([1200,600],[$info[0],$info[1]]);
    }

    public function test_a_large_legacy_image_can_be_displayed_while_googles_upload_limit_is_still_enforced(): void
    {
        $path=$this->image(); Storage::disk('public')->append($path,str_repeat('a',6*1024*1024));
        $photos=app(ProfilePhotoService::class); $this->assertNotSame($photos->placeholder(),$photos->url($path));
        $this->get($photos->url($path))->assertOk();
        try { app(GoogleContactProfile::class)->photoFile(new Persona(['foto'=>$path])); $this->fail('No se aplicó el límite de Google.'); }
        catch (GoogleContactsException $exception) { $this->assertSame('invalid_photo',$exception->reason); }
    }

    public function test_report_authors_use_their_own_linked_photos_and_load_repeated_users_in_two_queries(): void
    {
        $photo=$this->image(); $person=$this->persona(['foto'=>$photo]);
        $user=$this->actor('Padre'); $user->persona_id=$person->id; $user->foto='usuarios/sinfoto.jpg'; $user->save();
        $rows=collect([(object)['foto_user_id'=>$user->id],(object)['foto_user_id'=>$user->id]]);
        DB::enableQueryLog(); DB::flushQueryLog();
        app(ProfilePhotoService::class)->fillUserPhotos($rows);
        $queries=DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(2,$queries);
        foreach ($rows as $row) { $this->assertSame(app(ProfilePhotoService::class)->url($photo),$row->user_foto_url); }
        Http::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_imported_photo_urls_remain_displayable_without_server_side_fetches(): void
    {
        $photos=app(ProfilePhotoService::class); $url='https://example.test/photos/5300.jpg';
        $person=$this->persona(['foto'=>$url]); $user=$this->actor('Padre'); $user->foto=$url; $user->save();
        $this->assertSame($url,$photos->url($person->foto)); $this->assertSame($url,$user->adminlte_image());
        $this->getJson(route('persona.ajax'))->assertOk()->assertJsonPath('data.0.foto_url',$url);
        $this->assertSame($photos->placeholder(),$photos->url('https://user:password@example.test/image.jpg'));
        $this->assertSame($photos->placeholder(),$photos->url('javascript:alert(1)'));
        Http::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_new_user_registration_saves_the_photo_permanently_instead_of_a_temporary_upload_name(): void
    {
        $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>'Crear Usuarios','guard_name'=>'web']));
        $this->mock(\Illuminate\Contracts\Validation\UncompromisedVerifier::class)->shouldReceive('verify')->once()->andReturn(true);
        $password='LocalOnly!PhotoTest#72';
        $this->post(route('users.store'),['name'=>'Cuenta nueva','email'=>'photo-test@example.test','password'=>$password,
            'password_confirmation'=>$password,'foto'=>UploadedFile::fake()->image('profile.png',800,400)])->assertSessionHasNoErrors()->assertRedirect();
        $user=User::where('email','photo-test@example.test')->sole();
        Storage::disk('public')->assertExists($user->foto);
        $this->assertStringStartsWith('usuarios/',$user->foto);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($password,$user->password));
        $this->get($user->adminlte_image())->assertOk()->assertHeader('Content-Type','image/jpeg');
        Http::assertNothingSent(); Queue::assertNothingPushed();
    }
}
