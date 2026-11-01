<?php
namespace App\Http\Middleware;
use Closure;
class RequireInternalApi
{
    public static function publicRequest($request): bool
    {
        $path=trim(substr($request->path(),4),'/');
        $catalogs=['tipomotivos','grados','niveles','paises','ciudades','departamentos','provincias','municipios','zonas','colegios','modalidades','asignaturas','materias','estados','motivos','interests','comos','carreras','feriados'];
        return ($request->isMethod('GET')&&(in_array($path,$catalogs,true)||preg_match('~^(pais/\d+/(ciudades|departamentos)|ciudad/\d+/zonas|departamento/\d+/provincias|provincia/\d+/municipios|temas/\d+)$~D',$path)))
            ||($request->isMethod('POST')&&$path==='send-message');
    }
    public function handle($request,Closure $next)
    {
        // Public catalogs contain no account, person, attendance or financial records.
        $path=trim(substr($request->path(),4),'/');
        if (!$request->user()&&!self::publicRequest($request)) { return response()->json(['message'=>'Inicie sesión para consultar estos datos.'],401); }
        if ($request->user()) {
            $permissions=['computaciones'=>'Listar Computacion','administrativos'=>'Listar Administrativos','presentes'=>'Listar Clases',
                'usuarios'=>'Listar Usuarios','apoderados'=>'Listar Personas','inscripciones'=>'Listar Inscripciones','docentes/turnos'=>'Listar Docentes'];
            if (isset($permissions[$path])&&!$request->user()->hasRole('Admin')&&!$request->user()->can($permissions[$path])) { abort(403); }
        }
        return $next($request);
    }
}
