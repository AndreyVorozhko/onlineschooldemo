<?php

return [

	'sitemap_static' =>
	[
		[
			'url' => '',
			'view' => 'Photographer/index.html',
			'freq' => 'daily',
			'prio' => 1
		],
		[
			'url' => '/about',
			'view' => 'Main/about.html',
			'freq' => 'daily',
			'prio' => 0.9
		],
		[
			'url' => '/feedback',
			'view' => 'Main/feedback.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/cert',
			'view' => 'Main/underconstruct.html',
			'freq' => 'monthly',
			'prio' => 0.6
		],
		[
			'url' => '/user/addnew',
			'view' => 'User/addnew.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/login',
			'view' => 'User/login.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/login',
			'view' => 'User/login.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/agreement',
			'view' => 'User/agreement.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/agreement_commercial',
			'view' => 'User/agreement_commercial.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/agreereg',
			'view' => 'User/agreereg.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/cookie',
			'view' => 'User/cookie.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/disclaimer',
			'view' => 'User/disclaimer.html',
			'freq' => 'monthly',
			'prio' => 0.0
		],
		[
			'url' => '/user/privacy',
			'view' => 'User/privacy.html',
			'freq' => 'monthly',
			'prio' => 0.0
		]
	]
];