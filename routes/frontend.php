<?php

use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\HomeController;
use Illuminate\Support\Facades\Route;


Route::group(['as' => 'f.'], function () {
    Route::get('/', [HomeController::class, 'home'])->name('home');

    Route::get('/home-login', [HomeController::class, 'login'])->name('login');

    Route::get('/about', [HomeController::class, 'about'])->name('about');

    Route::get('/service', [HomeController::class, 'service'])->name('service');

    Route::get('/memberShip', [HomeController::class, 'memberShip'])->name('memberShip');

    
    Route::get('/insight', [HomeController::class, 'insight'])->name('insight');
    
    Route::get(';/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact-store', [ContactController::class, 'store'])->name('contact.store');
});
