<?php

use IlBronza\Contacts\Contacts;

Route::group([
	'middleware' => ['web', 'auth'],
	'prefix' => 'contact-management',
	'routeTranslationPrefix' => 'contacts::routes.',
	'as' => config('contacts.routePrefix')
	],
	function()
	{


Route::group([
	'prefix' => 'types',
	'middleware' => ['role:administrator|superadmin|contacts']
], function()
{
	Route::get('', [Contacts::getController('contacttype', 'index'), 'index'])->name('contacttypes.index');
	Route::get('create', [Contacts::getController('contacttype', 'create'), 'create'])->name('contacttypes.create');
	Route::post('', [Contacts::getController('contacttype', 'store'), 'store'])->name('contacttypes.store');
	Route::get('{contacttype}', [Contacts::getController('contacttype', 'show'), 'show'])->name('contacttypes.show');
	Route::get('{contacttype}/edit', [Contacts::getController('contacttype', 'edit'), 'edit'])->name('contacttypes.edit');
	Route::put('{contacttype}', [Contacts::getController('contacttype', 'edit'), 'update'])->name('contacttypes.update');
	Route::delete('{contacttype}/delete', [Contacts::getController('contacttype', 'destroy'), 'destroy'])->name('contacttypes.destroy');
});


Route::group([
	'prefix' => 'contacts',
	'middleware' => ['role:administrator|superadmin|contacts']
], function()
{
	Route::get('', [Contacts::getController('contact', 'index'), 'index'])->name('contacts.index');

	//ContactByModelController
	Route::get('by-model/{model}/{id}', [Contacts::getController('contact', 'byModel'), 'byModel'])->name('contacts.byModelFetched');
	// Route::get('create', [Contacts::getController('contact', 'create'), 'create'])->name('contacts.create');
	// Route::post('', [Contacts::getController('contact', 'store'), 'store'])->name('contacts.store');
	Route::get('{contact}', [Contacts::getController('contact', 'show'), 'show'])->name('contacts.show');
	 Route::get('{contact}/edit', [Contacts::getController('contact', 'edit'), 'edit'])->name('contacts.edit');
	 Route::put('{contact}', [Contacts::getController('contact', 'edit'), 'update'])->name('contacts.update');
	Route::delete('{contact}/delete', [Contacts::getController('contact', 'destroy'), 'destroy'])->name('contacts.destroy');
});


	// Route::resource('contacts', Contacts::getController('contact', 'index'));

	Route::get('contacts/create-by/{model}/{key}', [Contacts::getController('contact', 'createBy'), 'createBy'])->name('contacts.createBy');
	Route::post('contacts/store-by/{model}/{key}', [Contacts::getController('contact', 'storeBy'), 'storeBy'])->name('contacts.store');

	

		
	});