<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (): string {
    return 'Anugerah3D Customer Portal Ready';
})->name('home');
