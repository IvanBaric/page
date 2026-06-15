<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::admin.pages.index')->name('index');
Route::livewire('/create', 'pages::admin.pages.form')->name('create');
Route::livewire('/{page:uuid}/edit', 'pages::admin.pages.form')->name('edit');
Route::livewire('/{page:uuid}/sections', 'pages::admin.sections.manager')->name('sections');
Route::livewire('/{page:uuid}/sections/{section:uuid}/items', 'pages::admin.section-items.manager')->name('sections.items');
