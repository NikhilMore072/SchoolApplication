<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MastersController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';

route::get('/abc',function(){
echo('hello');
});
Route::get('/hello', [MastersController::class, 'hello']);
