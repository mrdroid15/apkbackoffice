<?php

use App\Models\Apkads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Public package lookup:   GET /api?package=com.example.app
 *
 * Returns the apkads row matching the given `packagename`, with a short-lived
 * pre-signed URL for the image (valid 1 hour; private R2 bucket can't serve
 * direct public URLs). Returns 400 if the query param is missing and 404 if
 * no matching record exists.
 *
 * Kept in routes/web.php intentionally — routes/api.php would pull in the
 * Sanctum/stateful-auth stack we don't need for a single public GET endpoint.
 * Laravel's CSRF middleware only applies to state-changing verbs (POST/PUT/
 * PATCH/DELETE), so a GET here is unaffected.
 */
Route::get('/api', function (Request $request) {
    $package = $request->query('package');

    if (! is_string($package) || $package === '') {
        return response()->json([
            'error' => 'package query parameter required',
        ], 400);
    }

    $apk = Apkads::where('packagename', $package)->first();

    if (! $apk) {
        return response()->json([
            'error'       => 'package not found',
            'packagename' => $package,
        ], 404);
    }

    return response()->json([
        'name'        => $apk->name,
        'packagename' => $apk->packagename,
        'image'       => $apk->image
            ? Storage::disk('s3')->temporaryUrl($apk->image, now()->addHour())
            : null,
        'link'        => $apk->link,
    ]);
});
