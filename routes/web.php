<?php
Route::get('/', 'ImageGalleryController@index');
Route::post('/', 'ImageGalleryController@upload');
Route::delete('/{id}', 'ImageGalleryController@destroy');
