<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use UxWeb\SweetAlert\SweetAlert as SweetAlert;
use Alert;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Crypt;

//use UxWeb\SweetAlert\SweetAlert as SweetAlertSweetAlert;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Usuarios')->only("index","show","share","listing");
        $this->middleware('can:Crear Usuarios')->only("create","crear","store","guardar");
        $this->middleware('can:Editar Usuarios')->only("edit","update","updatePassword");
        $this->middleware('can:Eliminar Usuarios')->only("destroy");
    }
    
    public function index()
    {
        $users = User::paginate();
        return view('user.index', compact('users'))
            ->with('i', (request()->input('page', 1) - 1) * $users->perPage());
    }

    public function listing()
    {
        return datatables()->of(User::with('persona:id,foto')->select('id','name','email','foto','persona_id'))
            ->addColumn('foto_url',function ($user) { return app(\App\Services\ProfilePhotoService::class)->forUser($user); })
            ->addColumn('btn','user.action')->rawColumns(['btn'])->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = new User();
        //SweetAlert::message('Robots are working!');
        SweetAlert::success('Success Message', 'Optional Title');
        return view('auth.register', compact('user'));
    }
    
    public function crear(Persona $persona)
    {
        return view('user.create',compact('persona'));
        
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request)
    {
        $data=$request->validated();
        $data['password']=Hash::make($data['password']);
        unset($data['foto']);
        if ($request->hasFile('foto')) { $data['foto']=app(\App\Services\ProfilePhotoService::class)->store($request->file('foto'),'usuarios'); }
        $user = User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }
    public function guardar(UserStoreRequest $request)
    {
        //dd($request->all());
        $usuario=new User();
        $usuario->name=$request->name;
        $usuario->email=$request->email;
        $usuario->password= Hash::make($request->password);

        if ($request->hasFile('foto')) {
            $usuario->foto = app(\App\Services\ProfilePhotoService::class)->store($request->file('foto'),'usuarios');

        }
        $usuario->save();
        $usuario->assignRole('Docente');
        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);

        return view('user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);

        return view('user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $user->name=$request->name;
        $user->email=$request->email;
        if($request->password != null){
            $user->password=Hash::make($request->password);
        }
        if ($request->hasFile('foto')) {
            $user->foto = app(\App\Services\ProfilePhotoService::class)->store($request->file('foto'),'usuarios');
        }
        $user->save();
        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate(
            [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:64',
                    'confirmed',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*#?&\-_.,;:]/',
                ],
            ],
            [
                'password.required' => 'La nueva contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.max' => 'La contraseña no puede exceder 64 caracteres.',
                'password.confirmed' => 'La confirmación de contraseña no coincide.',
                'password.regex' => 'La contraseña debe incluir mayúscula, minúscula, número y símbolo.',
            ]
        );

        $user->password = Hash::make($validated['password']);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 200,
                'message' => 'Contraseña actualizada correctamente.',
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();
        return response()->json(['message' => 'Registro Eliminado', 'status' => 200]);
    }
    
    public function share(User $user){
        $persona=$user->persona;
        $apoderados= $persona->apoderados;
        $user=$persona->user;
        $pass=ucfirst(strtolower($persona->nombre).$persona->id).'*';
        return view('user.telefonos',compact('persona','apoderados','user','pass'));
    }
    // public function quien(Request $request){
    //     $useres=User::join('userables','userables.user_id','users.id')
    //         ->where('userable_id',69)
    //         ->where('userables.userable_type',"App\\Models\\Observacion")
    //         ->select('name')
    //         ->get();
    //     return response()->json(['user'=>$useres,'d'=>2]);
    // }

}
