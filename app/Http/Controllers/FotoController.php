<?php
namespace App\Http\Controllers;

use App\Exceptions\GoogleContactsException;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;

class FotoController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function show(Request $request, ProfilePhotoService $photos, $filename)
    {
        try { $file=$photos->file($filename); }
        catch (GoogleContactsException $exception) { abort(404); }
        abort_unless($file,404);
        $response=response()->file($file['path'],['Content-Type'=>$file['mime'],
            'Cache-Control'=>'private, no-cache','X-Content-Type-Options'=>'nosniff']);
        $response->setAutoEtag(); $response->setAutoLastModified(); $response->isNotModified($request);
        return $response;
    }
}
