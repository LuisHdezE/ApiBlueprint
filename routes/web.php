<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('landing');
Route::view('/catalogo', 'catalog')->name('catalog.admin');
Route::view('/swagger', 'swagger')->name('swagger');
