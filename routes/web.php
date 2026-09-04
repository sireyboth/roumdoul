<?php

use App\Livewire\Pages\AboutPage;
use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\RegisterPage;
use App\Livewire\Pages\CartPage;
use App\Livewire\Pages\CheckoutPage;
use App\Livewire\Pages\ContactPage;
use App\Livewire\Pages\FeedbackPage;
use App\Livewire\Pages\Dashboard\DashboardPage;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\OrderConfirmationPage;
use App\Livewire\Pages\ServiceDetailPage;
use App\Livewire\Pages\ShopPage;
use App\Http\Controllers\InvitationRsvpController;
use App\Http\Controllers\InvitationShowController;
use App\Http\Controllers\InvitationTemplateDemoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');

Route::get('/shop', ShopPage::class)->name('shop');
Route::get('/shop/{category:slug}', ShopPage::class)->name('shop.category');
Route::get('/service/{service:slug}', ServiceDetailPage::class)->name('service.show');

Route::get('/cart', CartPage::class)->name('cart');

Route::get('/about', AboutPage::class)->name('about');
Route::get('/contact', ContactPage::class)->name('contact');
Route::get('/feedback', FeedbackPage::class)->middleware('throttle:20,1')->name('feedback');

Route::get('/templates/{template:slug}/demo', InvitationTemplateDemoController::class)->name('templates.demo');
Route::get('/invite/{invitation:slug}/{recipient:token}', InvitationShowController::class)->name('invitation.show');
Route::post('/invite/{invitation:slug}/{recipient:token}/rsvp', InvitationRsvpController::class)
    ->middleware('throttle:20,1')
    ->name('invitation.rsvp');

Route::middleware('guest:customer')->group(function () {
    Route::get('/register', RegisterPage::class)->name('register');
    Route::get('/login', LoginPage::class)->name('login');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/dashboard', DashboardPage::class)->name('dashboard');
    Route::get('/dashboard/invitations/{invitation}', InvitationManagePage::class)->name('dashboard.invitations.show');
    Route::get('/checkout', CheckoutPage::class)->name('checkout');
    Route::get('/order/{order}/confirmation', OrderConfirmationPage::class)->name('order.confirmation');

    Route::post('/logout', function () {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
