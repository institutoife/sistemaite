<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleContactsException;
use App\Models\Persona;
use App\Services\GoogleContactProfile;
use Illuminate\Console\Command;

class CheckGoogleContactPhoto extends Command
{
    protected $signature = 'gcontact:photo-check {persona : Código de Persona}';
    protected $description = 'Comprueba una fotografía local sin modificar datos, encolar contactos ni llamar a Google.';

    public function handle(GoogleContactProfile $profile): int
    {
        $id = (string) $this->argument('persona');
        if (!ctype_digit($id) || (int) $id < 1 || !($person = Persona::find($id))) {
            $this->error('No se encontró esa Persona. Indique su código numérico.');
            return self::FAILURE;
        }
        $this->line('Persona #'.$person->id.'. Comprobación local sin cambios ni solicitudes a Google.');
        $this->table(['Comprobación', 'Resultado'], [
            ['PHP CLI', PHP_VERSION], ['GD', extension_loaded('gd') ? 'Habilitada' : 'Falta habilitarla'],
            ['Restricción open_basedir', ini_get('open_basedir') ? 'Activa' : 'Sin restricción'],
        ]);
        try {
            $file = $profile->photoFile($person);
            $photo = $profile->photo($person);
            if (!$photo) {
                $this->info('Sin fotografía propia: el contacto se sincroniza conservando la foto que ya exista en Google.');
            } else {
                $this->info('Fotografía válida y preparada para Google. Ubicación: '.$file['location'].'.');
            }
            return self::SUCCESS;
        } catch (GoogleContactsException $exception) {
            $this->error($exception->reason.': '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
