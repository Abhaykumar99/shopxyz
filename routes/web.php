<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/dev.php';
}
