<?php
namespace App\Models;
use App\Services\SedeAccess;
use Illuminate\Database\Eloquent\{Model,Builder};
abstract class BranchModel extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('sede_access',function(Builder $builder){$query=$builder->getQuery();if($query instanceof \App\Support\SedeQueryBuilder)$query->applySedeGuard();});
    }
    protected function newBaseQueryBuilder() { return SedeAccess::query(parent::newBaseQueryBuilder()); }
    protected function performInsert(Builder $query)
    {
        return $this->getConnection()->transaction(function () use ($query) {
            $sede=app(SedeAccess::class)->prepare($this);
            $saved=parent::performInsert($query);
            if ($saved) { app(SedeAccess::class)->attribute($this,$sede); }
            return $saved;
        });
    }
    protected function performUpdate(Builder $query)
    {
        app(SedeAccess::class)->prepare($this);
        return parent::performUpdate($query);
    }
}
