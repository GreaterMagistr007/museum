<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/news', [PageController::class, 'news'])->name('news');
Route::get('/exposition', [PageController::class, 'exposition'])->name('exposition');
Route::get('/archive', [PageController::class, 'archive'])->name('archive');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');

Route::get('/excursions', [PageController::class, 'excursions'])->name('excursions');
Route::get('/excursion/{excursion:slug}', [PageController::class, 'excursionShow'])->name('excursion.show');

// Статьи (военный городок и подразделы)
Route::get('/article/{article:slug}', [PageController::class, 'articleShow'])->name('article.show');

// 301 редиректы со старых URL на новые slug-based
Route::redirect('/military-town', '/article/military-town', 301)->name('military-town');
Route::redirect('/junker-school', '/article/junker-school', 301)->name('junker-school');
Route::redirect('/infantry-courses', '/article/infantry-courses', 301)->name('infantry-courses');
Route::redirect('/topographic-unit', '/article/topographic-unit', 301)->name('topographic-unit');
