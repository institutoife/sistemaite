<?php

namespace App\Services;

use App\Exceptions\GoogleContactsException;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class GoogleContactProfile
{
    private const NOTE_START = '--- Datos de IFE ---';
    private const NOTE_END = '--- Fin de datos de IFE ---';

    public function fields(Persona $person): array
    {
        $relations = ['estudiante.inscripciones.modalidad', 'computacion.matriculaciones.asignatura'];
        $person->load(array_merge($relations, ['hijos' => function ($query) use ($relations) {
            $query->orderBy('personas.id')->with($relations);
        }]));
        $suffix = '| IFE | '.$person->id;
        $notes = ['IFE', 'Código de persona: '.$person->id, 'Nombre: '.$this->name($person)];
        $student = $person->estudiante || $person->computacion;
        if ($student) {
            $notes[] = 'Código de estudiante: '.$person->id;
        }
        $links = [];
        foreach ($person->hijos as $child) {
            $relation = $this->relationship($child->pivot->parentesco);
            $links[] = $relation.' de '.$this->name($child).' ('.$child->id.')';
            $notes[] = 'Relación: '.$relation.' de '.$this->name($child).' | Código: '.$child->id;
            foreach ($this->services($child) as $service) {
                $notes[] = '  Servicio de '.$this->name($child).': '.$service;
            }
        }
        if ($links) {
            if (count($links) === 1) {
                $child = $person->hijos->first();
                $link = $this->relationship($child->pivot->parentesco).' de '.$this->name($child).' | '.$child->id;
            } else {
                $link = implode('; ', array_slice($links, 0, 2));
                if (count($links) > 2) {
                    $link .= '; +'.(count($links) - 2);
                }
            }
            $suffix = ($student ? $suffix.' ' : '').'| '.$link;
        }
        foreach ($this->services($person) as $service) {
            $notes[] = 'Servicio registrado: '.$service;
        }
        $fields = ['names' => [['givenName' => trim((string) $person->nombre),
            'familyName' => trim($person->apellidop.' '.$person->apellidom),
            'honorificSuffix' => mb_strimwidth($suffix, 0, 200, '…')]],
            // Biography is merged into the existing note before PATCH (a singleton in People API).
            'biographies' => [['value' => implode("\n", $notes), 'contentType' => 'TEXT_PLAIN']]];
        $rawBirthday = trim((string) $person->getRawOriginal('fechanacimiento'));
        if ($rawBirthday !== '' && $rawBirthday !== '0000-00-00') {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: 00:00:00)?$/', $rawBirthday, $date)
                || !checkdate((int) $date[2], (int) $date[3], (int) $date[1])) {
                throw new GoogleContactsException('invalid_birthday', 'Revise la fecha de nacimiento antes de sincronizar.');
            }
            $fields['birthdays'] = [['date' => ['year' => (int) $date[1], 'month' => (int) $date[2], 'day' => (int) $date[3]]]];
        }
        return $fields;
    }

    private function name(Persona $person): string
    {
        return trim(preg_replace('/\s+/u', ' ', $person->nombre.' '.$person->apellidop.' '.$person->apellidom));
    }

    private function relationship(?string $value): string
    {
        $value = trim((string) $value);
        return ['PAPA' => 'Papá', 'PAPÁ' => 'Papá', 'PADRE' => 'Papá', 'MAMA' => 'Mamá', 'MAMÁ' => 'Mamá',
            'MADRE' => 'Mamá', 'ABUELO' => 'Abuelo', 'ABUELA' => 'Abuela', 'TIO' => 'Tío', 'TIA' => 'Tía',
            'TUTOR' => 'Tutor', 'TUTORA' => 'Tutora', 'HERMANO' => 'Hermano', 'HERMANA' => 'Hermana']
            [mb_strtoupper($value)] ?? ($value !== '' && mb_strtoupper($value) !== 'OTRO' ? $value : 'Apoderado');
    }

    private function services(Persona $person): array
    {
        $services = [];
        foreach (optional($person->estudiante)->inscripciones ?? [] as $enrolment) {
            if ($name = trim((string) optional($enrolment->modalidad)->modalidad)) {
                $services[] = $name;
            }
        }
        foreach (optional($person->computacion)->matriculaciones ?? [] as $enrolment) {
            if ($name = trim((string) optional($enrolment->asignatura)->asignatura)) {
                $services[] = 'Computación: '.$name;
            }
        }
        $services = array_values(array_unique($services));
        sort($services, SORT_STRING);
        return $services;
    }

    public function biography(string $note, array $remote = []): array
    {
        $previous = '';
        $type = 'TEXT_PLAIN';
        foreach ($remote['biographies'] ?? [] as $biography) {
            if (isset($biography['metadata']['source']['type']) && $biography['metadata']['source']['type'] !== 'CONTACT') {
                continue;
            }
            $previous = $biography['value'] ?? '';
            $type = ($biography['contentType'] ?? '') === 'TEXT_HTML' ? 'TEXT_HTML' : 'TEXT_PLAIN';
            break;
        }
        $pattern = '~'.preg_quote(self::NOTE_START, '~').'.*?'.preg_quote(self::NOTE_END, '~').'~s';
        $previous = trim(preg_replace($pattern, '', $previous));
        if ($type === 'TEXT_HTML') {
            $note = nl2br(htmlspecialchars($note, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }
        $managed = self::NOTE_START."\n".$note."\n".self::NOTE_END;
        return [['value' => ($previous !== '' ? $previous."\n" : '').$managed, 'contentType' => $type]];
    }

    public function photo(Persona $person): ?array
    {
        $file = $this->photoFile($person);
        if (!$file) { return null; }
        // Bound the read even if an upload is replaced between the size check and this read.
        $bytes = @file_get_contents($file['path'], false, null, 0, 5 * 1024 * 1024 + 1);
        if ($bytes === false) {
            throw new GoogleContactsException('invalid_photo', 'La fotografía propia existe, pero PHP no puede leerla. Revise los permisos del archivo.');
        }
        if (strlen($bytes) > 5 * 1024 * 1024) {
            throw new GoogleContactsException('invalid_photo', 'La fotografía propia supera el tamaño permitido de 5 MB. Vuelva a subir una imagen más pequeña.');
        }
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] * $info[1] > 20000000) {
            throw new GoogleContactsException('invalid_photo', 'La fotografía debe ser una imagen JPG, PNG o WebP válida de hasta 5 MB y 20 megapíxeles.');
        }
        if (!extension_loaded('gd')) {
            throw new GoogleContactsException('photo_extension', 'El servidor necesita la extensión GD de PHP para sincronizar fotografías.');
        }
        $input = @imagecreatefromstring($bytes);
        if (!$input) {
            throw new GoogleContactsException('invalid_photo', 'No se pudo interpretar la fotografía de esta persona.');
        }
        $scale = min(1, 720 / max($info[0], $info[1]));
        $output = imagecreatetruecolor(max(1, (int) round($info[0] * $scale)), max(1, (int) round($info[1] * $scale)));
        try {
            imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
            imagecopyresampled($output, $input, 0, 0, 0, 0, imagesx($output), imagesy($output), $info[0], $info[1]);
            ob_start();
            imagejpeg($output, null, 85);
            $bytes = ob_get_clean();
        } finally {
            imagedestroy($input);
            imagedestroy($output);
        }
        return ['hash' => hash('sha256', $bytes), 'bytes' => base64_encode($bytes)];
    }

    public function photoFile(Persona $person): ?array
    {
        $stored = (string) $person->foto;
        if (strpos($stored, "\0") !== false) {
            throw new GoogleContactsException('invalid_photo', 'La ruta de la fotografía no es una ruta local válida. Vuelva a guardar la foto desde la ficha.');
        }
        $relative = str_replace('\\', '/', trim($stored));
        if ($relative === '' || preg_match('~^(sinfoto|sinperfil|foto|default|avatar|no[-_]?photo)\.(jpe?g|png|gif|webp)$~i', basename($relative))) {
            return null;
        }
        $alias = preg_replace('~^/?(?:storage/app/public|public/storage|storage|public/foto|foto)/~', '', $relative, 1, $aliases);
        if (strpos($relative, "\0") !== false || preg_match('~(:|(^|/)\.\.(/|$)|^//)~', $relative)
            || ($relative[0] === '/' && !$aliases)) {
            throw new GoogleContactsException('invalid_photo', 'La ruta de la fotografía no es una ruta local válida. Vuelva a guardar la foto desde la ficha.');
        }
        $variants = array_unique($aliases ? [$relative, $alias] : [$relative]);
        // Some shared-hosting installations serve an actual public/storage directory, not a link.
        // Use only explicit upload roots and the exact saved filename; never fetch URLs or search by basename.
        $roots = ['disco público' => Storage::disk('public')->path(''),
            'public/storage' => public_path('storage'), 'public/foto' => public_path('foto')];
        $visited = [];
        foreach ($roots as $location => $directory) {
            $root = @realpath($directory);
            if (!$root || in_array($root, $visited, true)) { continue; }
            $visited[] = $root;
            $prefix = $root.DIRECTORY_SEPARATOR;
            foreach ($variants as $variant) {
                $candidate = $prefix.ltrim($variant, '/');
                clearstatcache(true, $candidate);
                $path = @realpath($candidate);
                if (!$path) { continue; }
                $inside = DIRECTORY_SEPARATOR === '\\'
                    ? strncasecmp($path, $prefix, strlen($prefix)) === 0 : strpos($path, $prefix) === 0;
                if (!$inside) {
                    throw new GoogleContactsException('invalid_photo', 'La fotografía apunta fuera del directorio de imágenes permitido. Vuelva a subirla desde la ficha.');
                }
                if (!is_file($path)) { continue; }
                if (!is_readable($path)) {
                    throw new GoogleContactsException('invalid_photo', 'La fotografía propia existe, pero PHP no puede leerla. Revise los permisos del archivo.');
                }
                if (filesize($path) > 5 * 1024 * 1024) {
                    throw new GoogleContactsException('invalid_photo', 'La fotografía propia supera el tamaño permitido de 5 MB. Vuelva a subir una imagen más pequeña.');
                }
                return ['path' => $path, 'location' => $location];
            }
        }
        throw new GoogleContactsException('invalid_photo', 'No se encontró la fotografía propia en los directorios de imágenes del servidor. Vuelva a subirla desde la ficha.');
    }
}
