<?php

/*
|--------------------------------------------------------------------------
| Personal portfolio routes
|--------------------------------------------------------------------------
|
| /portfolio/{portfolio}                         → redirect to a language
| /portfolio/{portfolio}/{locale}                → home
| /portfolio/{portfolio}/{locale}/work/{slug}    → case study
| … one folder in portfolios/ per {portfolio}. See portfolios/README.md.
|
*/

use App\Http\Controllers\Portfolio\PortfolioContactController;
use App\Http\Controllers\Portfolio\PortfolioController;
use Illuminate\Support\Facades\Route;

$locales = array_keys(config('portfolio.locales'));

Route::prefix(config('portfolio.route_prefix').'/{portfolio}')
    ->name('portfolio.')
    ->group(function () use ($locales) {
        Route::get('/', [PortfolioController::class, 'root'])->name('root');

        Route::get('/media/{path}', [PortfolioController::class, 'media'])
            ->where('path', '.*')
            ->name('media');

        Route::prefix('{locale}')
            ->whereIn('locale', $locales)
            ->group(function () {
                Route::get('/', [PortfolioController::class, 'home'])->name('home');
                Route::get('/about', [PortfolioController::class, 'about'])->name('about');
                Route::get('/work', [PortfolioController::class, 'work'])->name('work');
                Route::get('/work/{project}', [PortfolioController::class, 'project'])->name('work.show');
                Route::get('/expertise', [PortfolioController::class, 'expertise'])->name('expertise');
                Route::get('/notes', [PortfolioController::class, 'notes'])->name('notes');
                Route::get('/notes/{note}', [PortfolioController::class, 'note'])->name('notes.show');
                Route::get('/contact', [PortfolioController::class, 'contact'])->name('contact');
                Route::post('/contact', PortfolioContactController::class)
                    ->middleware('throttle:5,10')
                    ->name('contact.send');
            });
    });
