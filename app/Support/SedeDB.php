<?php
namespace App\Support;
use App\Services\SedeAccess;
/** Adds branch predicates to existing Query Builder calls; other facade methods remain Laravel's. */
class SedeDB extends \Illuminate\Support\Facades\DB
{
    public static function table($table,$as=null) { return SedeAccess::query(static::getFacadeRoot()->table($table,$as)); }
}
