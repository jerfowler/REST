<?php defined('SYSPATH') or die('No direct script access.');

// Basic catch-all route for REST controller
Route::set('rest', 'rest/<action>((/<id>)(.<content_type>))')
	->defaults(array(
		'controller' => 'rest'
	));

