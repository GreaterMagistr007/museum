<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('pages.home'))->name('home');
Route::get('/news', fn () => view('pages.news'))->name('news');
Route::get('/exposition', fn () => view('pages.exposition'))->name('exposition');
Route::get('/archive', fn () => view('pages.archive'))->name('archive');
Route::get('/about', fn () => view('pages.about'))->name('about');
Route::get('/contacts', fn () => view('pages.contacts'))->name('contacts');
Route::get('/excursions', fn () => view('pages.excursions'))->name('excursions');

Route::get('/military-town', fn () => view('pages.military-town'))->name('military-town');
Route::get('/junker-school', fn () => view('pages.junker-school'))->name('junker-school');
Route::get('/infantry-courses', fn () => view('pages.infantry-courses'))->name('infantry-courses');
Route::get('/topographic-unit', fn () => view('pages.topographic-unit'))->name('topographic-unit');

Route::get('/excursion/overview', fn () => view('pages.excursion-overview'))->name('excursion-overview');
Route::get('/excursion/junker', fn () => view('pages.excursion-junker'))->name('excursion-junker');
Route::get('/excursion/awards', fn () => view('pages.excursion-awards'))->name('excursion-awards');
Route::get('/excursion/topographic-service', fn () => view('pages.excursion-topographic-service'))->name('excursion-topographic-service');
Route::get('/excursion/irkutsk-topographic', fn () => view('pages.excursion-irkutsk-topographic'))->name('excursion-irkutsk-topographic');
Route::get('/excursion/documents', fn () => view('pages.excursion-documents'))->name('excursion-documents');
