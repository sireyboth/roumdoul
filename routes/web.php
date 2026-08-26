<?php

use App\Livewire\Pages\AboutPage;
use App\Livewire\Pages\CartPage;
use App\Livewire\Pages\CheckoutPage;
use App\Livewire\Pages\ContactPage;
use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\OrderConfirmationPage;
use App\Livewire\Pages\ServiceDetailPage;
use App\Livewire\Pages\ShopPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');

Route::get('/shop', ShopPage::class)->name('shop');
Route::get('/shop/{category:slug}', ShopPage::class)->name('shop.category');
Route::get('/service/{service:slug}', ServiceDetailPage::class)->name('service.show');

Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', CheckoutPage::class)->name('checkout');
Route::get('/order/{order}/confirmation', OrderConfirmationPage::class)->name('order.confirmation');

Route::get('/about', AboutPage::class)->name('about');
Route::get('/contact', ContactPage::class)->name('contact');
