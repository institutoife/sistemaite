<?php

// Standalone preflight: no Laravel bootstrap, database access, writes or HTTP requests.
function ifeGoogleVendorPackageErrors(array $lock, array $installed): array
{
    $packages = $installed['packages'] ?? $installed;
    $versions = [];
    foreach ($packages as $package) {
        if (is_array($package) && isset($package['name'], $package['version'])) {
            $versions[$package['name']] = ltrim($package['version'], 'v');
        }
    }
    $errors = [];
    foreach ($lock['packages'] ?? [] as $package) {
        if (!isset($package['name'], $package['version'])) {
            $errors[] = 'composer.lock contiene un paquete sin nombre o versión.';
            continue;
        }
        if (($versions[$package['name']] ?? null) !== ltrim($package['version'], 'v')) {
            $errors[] = 'Dependencia ausente o de otra versión: '.$package['name'].'.';
        }
    }
    return $errors;
}

function ifeGoogleExistingVendorErrors(string $root): array
{
    $errors = [];
    foreach (['vendor/autoload.php', 'vendor/composer/installed.json', 'composer.lock'] as $relative) {
        if (!is_readable($root.'/'.$relative)) {
            $errors[] = 'Falta '.$relative.'.';
        }
    }
    if ($errors) {
        return $errors;
    }
    $lock = json_decode(file_get_contents($root.'/composer.lock'), true);
    $installed = json_decode(file_get_contents($root.'/vendor/composer/installed.json'), true);
    if (!is_array($lock) || empty($lock['packages']) || !is_array($installed)) {
        return ['No se pudo verificar composer.lock contra las dependencias instaladas.'];
    }
    $errors = ifeGoogleVendorPackageErrors($lock, $installed);
    if ($errors) {
        return $errors;
    }
    foreach (['curl', 'dom', 'fileinfo', 'gd', 'json', 'mbstring', 'openssl', 'pdo_mysql',
        'tokenizer', 'xml', 'xmlreader', 'xmlwriter', 'zip'] as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = 'Falta habilitar la extensión PHP '.$extension.'.';
        }
    }
    try {
        // Composer's generated platform check also runs here, when present.
        require $root.'/vendor/autoload.php';
        if (!class_exists(\Composer\Semver\Semver::class)) {
            return ['No se pudo cargar el validador de versiones existente. Verificar vendor con Composer.'];
        }
        $errors = array_merge($errors, ifeGoogleVendorPlatformErrors($lock, PHP_VERSION, function ($extension) {
            return extension_loaded($extension);
        }));
        foreach ([
            \App\Console\Commands\CheckGoogleContacts::class,
            \App\Console\Commands\WorkGoogleContacts::class,
            \App\Console\Commands\SyncGoogleContactsBatch::class,
            \App\Exceptions\GoogleContactsException::class,
            \App\Http\Controllers\GoogleContactsController::class,
            \App\Jobs\SyncGoogleContact::class,
            \App\Models\GoogleContactAccount::class,
            \App\Observers\PersonaObserver::class,
            \App\Observers\GoogleLeadObserver::class,
            \App\Observers\GoogleContactServiceObserver::class,
            \App\Services\GoogleContactsService::class,
            \App\Services\GoogleContactProfile::class,
            \App\Services\GoogleContactsRuntime::class,
            \App\Services\TelefonoService::class,
            \Database\Seeders\GoogleContactsSeeder::class,
        ] as $class) {
            if (!class_exists($class)) {
                $errors[] = 'El autoload no puede cargar '.$class.'. Regenerarlo con Composer.';
            }
        }
    } catch (\Throwable $exception) {
        $errors[] = 'PHP o el autoload existente no pueden cargar esta entrega. Revisar la versión de PHP y vendor.';
    }
    return array_values(array_unique($errors));
}

function ifeGoogleVendorPlatformErrors(array $lock, string $phpVersion, callable $hasExtension): array
{
    $requirements = [$lock['platform'] ?? []];
    foreach ($lock['packages'] ?? [] as $package) {
        $requirements[] = $package['require'] ?? [];
    }
    $errors = [];
    foreach ($requirements as $requirement) {
        foreach ($requirement as $name => $constraint) {
            if ($name === 'php' && !\Composer\Semver\Semver::satisfies($phpVersion, $constraint)) {
                $errors[] = 'PHP '.$phpVersion.' no cumple la versión requerida: '.$constraint.'.';
            } elseif (strpos($name, 'ext-') === 0 && !$hasExtension(substr($name, 4))) {
                $errors[] = 'Falta habilitar la extensión PHP '.substr($name, 4).'.';
            }
        }
    }
    return array_values(array_unique($errors));
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $errors = ifeGoogleExistingVendorErrors(dirname(__DIR__, 2));
    if ($errors) {
        foreach ($errors as $error) {
            fwrite(STDERR, $error.PHP_EOL);
        }
        fwrite(STDERR, 'La comprobación se detuvo antes de las migraciones. Corregir PHP/vendor o indicar COMPOSER_BIN con la ruta real de Composer.'.PHP_EOL);
        exit(1);
    }
    echo 'Dependencias existentes y autoload de Google Contacts verificados. Se puede continuar sin el ejecutable Composer.'.PHP_EOL;
}
