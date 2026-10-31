<?php
namespace App\Services;

use App\Exceptions\GoogleContactsException;
use App\Models\{Persona,User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class ProfilePhotoService
{
    public const RULES='nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120|dimensions:max_width=4000,max_height=4000';

    public function placeholder(): string { return asset('assets/img/profile-placeholder.svg'); }

    public function file(?string $stored): ?array
    {
        $person=new Persona(['foto'=>$this->localName($stored)]);
        $file=app(GoogleContactProfile::class)->photoFile($person,false);
        if (!$file) { return null; }
        $info=@getimagesize($file['path']);
        if (!$info||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_GIF,IMAGETYPE_WEBP],true)) {
            throw new GoogleContactsException('invalid_photo','El archivo guardado no es una fotografía JPG, PNG, GIF o WebP válida.');
        }
        return $file+['mime'=>$info['mime']];
    }

    private function localName(?string $stored): string
    {
        $stored=str_replace('\\','/',trim((string)$stored));
        if (preg_match('~^https?://~i',$stored)) {
            $url=parse_url($stored); $base=parse_url(config('app.url'));
            if (!$url||strcasecmp($url['host']??'',$base['host']??'')!==0||isset($url['user'])||isset($url['pass'])) {
                throw new GoogleContactsException('invalid_photo','La fotografía no corresponde a un archivo local del sistema.');
            }
            $path=rawurldecode($url['path']??''); $prefix=rtrim($base['path']??'','/');
            if ($prefix&&strpos($path,$prefix.'/')===0) { $path=substr($path,strlen($prefix)); }
            if (!preg_match('~^/(?:storage|foto|public/storage|public/foto|storage/app/public)/~',$path)) {
                throw new GoogleContactsException('invalid_photo','La URL no corresponde a un directorio local de fotografías.');
            }
            $stored=$path;
        }
        return $stored;
    }

    public function url(?string $stored): string
    {
        try {
            $name=$this->localName($stored); $file=$this->file($name);
            return $file?route('foto.show',['filename'=>ltrim($name,'/'),'v'=>filemtime($file['path'])]):$this->placeholder();
        } catch (GoogleContactsException $exception) {
            // Imported records may already store an image URL. Display it in the browser only.
            // Google uploads and the file endpoint still accept local files exclusively.
            $parts=parse_url((string)$stored);
            return filter_var($stored,FILTER_VALIDATE_URL)&&in_array(strtolower($parts['scheme']??''),['http','https'],true)
                &&!isset($parts['user'])&&!isset($parts['pass'])?(string)$stored:$this->placeholder();
        }
    }

    public function forUser($user): string
    {
        if (!$user) { return $this->placeholder(); }
        $own=$this->url($user->foto??null);
        if ($own!==$this->placeholder()) { return $own; }
        if ($user instanceof User&&$user->persona) {
            $linked=$this->url($user->persona->foto);
            if ($linked!==$this->placeholder()) { return $linked; }
        }
        return $this->placeholder();
    }

    public function fillUserPhotos(\Illuminate\Support\Collection $rows, string $key='foto_user_id'): void
    {
        $ids=$rows->pluck($key)->filter()->unique();
        $users=$ids->isEmpty()?collect():User::with('persona:id,foto')->whereIn('id',$ids)->get(['id','foto','persona_id'])->keyBy('id');
        $urls=$users->map(function ($user) { return $this->forUser($user); });
        foreach ($rows as $row) { $row->user_foto_url=$urls[$row->$key]??$this->placeholder(); }
    }

    public function store(UploadedFile $file, string $directory='estudiantes'): string
    {
        Validator::make(['foto'=>$file],['foto'=>self::RULES])->validate();
        return $this->write(Image::make($file)->orientate(),$directory);
    }

    public function storeCamera(string $data): string
    {
        if (strlen($data)>7*1024*1024||!preg_match('~^data:image/(?:png|jpeg);base64,(.+)$~s',$data,$match)) {
            throw ValidationException::withMessages(['imagen'=>'Tome una fotografía válida antes de guardar.']);
        }
        $bytes=base64_decode(str_replace(' ','+',$match[1]),true); $info=$bytes?@getimagesizefromstring($bytes):false;
        if (!$info||strlen($bytes)>5*1024*1024||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG],true)||$info[0]>4000||$info[1]>4000) {
            throw ValidationException::withMessages(['imagen'=>'La fotografía debe ser JPG o PNG de hasta 5 MB y 4000 × 4000 píxeles.']);
        }
        return $this->write(Image::make($bytes),'estudiantes');
    }

    private function write($image, string $directory): string
    {
        if (!in_array($directory,['estudiantes','usuarios','apoderados'],true)) { throw new \InvalidArgumentException('Directorio de fotografías inválido.'); }
        $name=$directory.'/'.Str::random(32).'.jpg';
        $image->resize(1600,1600,function ($constraint) { $constraint->aspectRatio(); $constraint->upsize(); });
        try {
            if (!Storage::disk('public')->put($name,(string)$image->encode('jpg',85))) {
                throw ValidationException::withMessages(['foto'=>'No se pudo guardar la fotografía. Intente nuevamente.']);
            }
        } finally { $image->destroy(); }
        // Old files may still belong to linked users or history. Never delete them during replacement.
        return $name;
    }
}
