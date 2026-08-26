<?php

use App\Livewire\Pages\AboutPage;
use App\Livewire\Pages\ContactPage;
use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\ServicesPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/services', ServicesPage::class)->name('services');
Route::get('/about', AboutPage::class)->name('about');
Route::get('/contact', ContactPage::class)->name('contact');
