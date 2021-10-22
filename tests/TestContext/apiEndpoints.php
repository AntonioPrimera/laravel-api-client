<?php

return [
	//provider name
	'mySanctumProvider' => [
		//authentication data
		'authentication' => [
			'type' => 'sanctum',
			'token' => env('SANCTUM_TOKEN_MY_PROVIDER'),
		],
		
		//common provider data
		'rootUrl'		 => 'https://localhost:8080',
		
		//all api endpoints for this provider
		'endpoints' => [
			'getTracks' => [
				'url' => '/tracks',
				'method' => 'get',
			],
			
			'getPositions' => '/positions',
			
			'syncApiCredentials' => [
				'url' => 'sync/api-credentials',
				//by default method is 'get', if not provided
			]
		],
	],
	
	//provider name
	'myHttpProvider' => [
		//authentication data
		'authentication' => [
			'type' => 'http:basic',
			
			//'credentials' => null,		//for no authentication necessary
			
			//'credentials' => [			//basic http authentication
			//	'username' => 'user-name',
			//	'password' => 'my-pass-word',
			//],
		],
		
		//common provider data
		'rootUrl'		 => 'https://localhost:8080',
		
		//all api endpoints for this provider
		'endpoints' => [
			'getTracks' => [
				'url' => '/tracks',
				'method' => 'get',
			],
			
			'getPositions' => '/positions',
			
			'syncApiCredentials' => [
				'url' => 'sync/api-credentials',
				//by default method is 'get', if not provided
			]
		],
	],
];