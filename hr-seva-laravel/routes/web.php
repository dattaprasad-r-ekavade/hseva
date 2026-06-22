<?php

use App\Support\NavigationBuilder;
use Illuminate\Support\Facades\Route;

require __DIR__.'/portal.php';

foreach (NavigationBuilder::cleanRoutes() as $clean => $legacy) {
    Route::redirect($clean, '/'.$legacy, 301);
    Route::redirect($clean.'/', '/'.$legacy, 301);
}
