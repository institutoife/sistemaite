<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Existing Spatie permissions can grant access; administrators retain setup access.
        foreach (['Listar Contactos', 'Sincronizar Contactos', 'Configurar Google Contacts', 'Ver Diagnostico Google'] as $permission) {
            Gate::define($permission, function ($user) {
                return $user->hasRole('Admin');
            });
        }

        Gate::define('gestionar-productos-admin', function ($user) {
            return $user->hasRole(['Admin']);
        });

        Gate::define('solo-admin-menu', function ($user) {
            return $user->hasRole(['Admin']);
        });

        Gate::define('menu-padres-estudiantes', function ($user) {
            return $user->hasRole(['Admin', 'Padre', 'Estudiante']);
        });

        Gate::define('vender-productos', function ($user) {
            return $user->hasRole(['Admin', 'Secretaria']);
        });

        Gate::define('ver-mis-ventas-propios', function ($user) {
            return $user->hasRole(['Admin', 'Secretaria']);
        });

        Gate::define('solo-docente-menu', function ($user) {
            return $user->hasRole(['Docente']);
        });
    }
}
