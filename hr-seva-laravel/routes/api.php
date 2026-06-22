<?php

use App\Http\Controllers\LegacyApiController;
use Illuminate\Support\Facades\Route;

Route::any('/{path?}', LegacyApiController::class)->where('path', '.*');
