<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    protected function decrypt(\Symfony\Component\HttpFoundation\Request $request)
    {
        $request=parent::decrypt($request);
        // A persistent 2FA cookie may skip the code, never the password login.
        $request->cookies->remove(app('auth')->guard('web')->getRecallerName());
        return $request;
    }
}
