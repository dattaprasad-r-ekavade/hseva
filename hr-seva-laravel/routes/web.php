<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $index = public_path('index.html');
    if (is_file($index)) {
        return response()->file($index);
    }

    return view('welcome');
});

Route::get('/{path}', function (string $path) {
    $file = public_path($path);
    if (is_file($file)) {
        return response()->file($file);
    }

    $dirIndex = public_path($path.'/index.html');
    if (is_file($dirIndex)) {
        return response()->file($dirIndex);
    }

    $fallback = public_path('index.html');
    if (is_file($fallback)) {
        return response()->file($fallback);
    }

    abort(404);
})->where('path', '^(?!api(?:/|$)).*');
