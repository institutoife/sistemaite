<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
$segments=[];
foreach(explode('/',str_replace('\\','/',$uri)) as $segment){
    if($segment===''||$segment==='.')continue;
    if($segment==='..'){array_pop($segments);}else{$segments[]=$segment;}
}
$privatePhoto=preg_match('~^/(?:foto/|storage/(?:estudiantes|usuarios|apoderados|docentes|personas)/)~i','/'.implode('/',$segments));
if($privatePhoto){
    // Existing static files otherwise become Symfony's base URL in PHP's development server.
    $_SERVER['SCRIPT_FILENAME']=__DIR__.'/public/index.php';
    $_SERVER['SCRIPT_NAME']='/index.php';
    $_SERVER['PHP_SELF']='/index.php';
}
if (!$privatePhoto && $uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
