<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('chatbot', 'pages::chatbot')->name('chatbot');
    Route::livewire('chatbot/{conversation}', 'pages::chatbot')->name('chatbot.conversation');
});

require __DIR__.'/settings.php';
