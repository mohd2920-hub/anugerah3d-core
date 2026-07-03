<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (): string {
    return 'Anugerah3D Agent Portal Ready';
})->name('home');
