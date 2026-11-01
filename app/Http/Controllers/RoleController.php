<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Roles')->only('index');
        $this->middleware('can:Crear Roles')->only('create','store');
        $this->middleware('can:Editar Roles')->only('edit','update');
        $this->middleware('can:Eliminar Roles')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'permissions' => 'required'
        ]);

        $role = Role::create([
            'name' => $request->name
        ]);

        $role->syncPermissions($request->permissions);

        //dd($role);
        return redirect()->route('role.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('roles.edit', compact('role','permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        if (app(\App\Services\AccountSecurity::class)->ready()) {
            $this->authorize('administer',\App\Models\User::class);
            abort_if($role->name==='Admin'&&$request->name!=='Admin',422,'El rol Admin debe conservarse.');
        }
        $request->validate([
            'name' => 'required',
            'permissions' => 'required'
        ]);

        \Illuminate\Support\Facades\DB::transaction(function()use($request,$role){
            $users=$role->users()->lockForUpdate()->get();
            $before=['role'=>$role->name,'permissions'=>$role->permissions()->pluck('name')->all()];
            $role->update(['name'=>$request->name]);$role->syncPermissions($request->permissions);
            if(app(\App\Services\AccountSecurity::class)->ready()){
                $security=app(\App\Services\AccountSecurity::class);
                foreach($users as $user){$security->revoke($user);$security->audit($user,'role_permissions_changed',$before,['role'=>$role->name,'permissions'=>$role->permissions()->pluck('name')->all()],'Actualización administrativa del rol');}
            }
        });

        return redirect()->route('role.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        if (app(\App\Services\AccountSecurity::class)->ready()) {
            $this->authorize('administer',\App\Models\User::class);
            abort_if($role->name==='Admin',422,'El rol Admin no puede eliminarse.');
        }
        \Illuminate\Support\Facades\DB::transaction(function()use($role){
            $users=$role->users()->lockForUpdate()->get();$name=$role->name;$role->delete();
            if(app(\App\Services\AccountSecurity::class)->ready())foreach($users as $user){$security=app(\App\Services\AccountSecurity::class);$security->revoke($user);$security->audit($user,'role_removed',['role'=>$name],[],'Eliminación administrativa del rol');}
        });
        return redirect()->route('role.index');
    }
}
