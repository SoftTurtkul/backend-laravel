<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return view('index');
});

Route::get('/{any}', function() {
    return redirect('/');
})->where('any','.*');
