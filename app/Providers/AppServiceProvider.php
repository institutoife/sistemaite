<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\App\Services\GoogleContactsService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Schema::defaultStringLength(191);
        date_default_timezone_set('America/La_Paz');
        Paginator::useBootstrap();
        \App\Models\Persona::observe(\App\Observers\PersonaObserver::class);
        \App\Models\Prospecto::observe(\App\Observers\GoogleLeadObserver::class);
        \App\Models\Comentario::observe(\App\Observers\GoogleLeadObserver::class);
        foreach ([\App\Models\Inscripcione::class, \App\Models\Matriculacion::class,
            \App\Models\Modalidad::class, \App\Models\Asignatura::class] as $model) {
            $model::observe(\App\Observers\GoogleContactServiceObserver::class);
        }
        foreach ([\App\Models\Estudiante::class, \App\Models\Computacion::class,
            \App\Models\Docente::class, \App\Models\Administrativo::class, \App\Models\Cliservicio::class,
            \App\Models\Proveedor::class, \App\Models\Clicopy::class] as $model) {
            $model::created(function ($record) {
                if ($persona = \App\Models\Persona::find($record->persona_id)) {
                    app(\App\Services\GoogleContactsService::class)->markPending($persona, auth()->id());
                }
            });
        }
    }
}
