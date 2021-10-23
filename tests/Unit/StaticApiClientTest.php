<?php
namespace AntonioPrimera\ApiClient\Tests\Unit;

use AntonioPrimera\ApiClient\ApiClient;
use AntonioPrimera\ApiClient\Clients\HttpClient;
use AntonioPrimera\ApiClient\Clients\LaravelSanctumClient;
use AntonioPrimera\ApiClient\Exceptions\BadApiEndpointConfig;
use AntonioPrimera\ApiClient\Exceptions\InvalidApiClientType;
use AntonioPrimera\ApiClient\Tests\TestCase;

class StaticApiClientTest extends TestCase
{
	/** @test */
	public function api_client_is_created_and_cached_correctly()
	{
		config(['apiEndpoints' => [
			'mySanctumClient' => [
				'authentication' => ['type' => 'sanctum',]
			],
			'myHttpClient' => [
				'authentication' => ['type' => 'http:basic',]
			],
			'myBadClient' => [
				'authentication' => ['type' => 'blabla',]
			],
			'myDefaultHttpClient' => [
				//no authentication
				'endpoints' => [],
			],
		]]);
		
		$this->assertFalse(ApiClient::clientExists('mySanctumClient'));
		$this->assertInstanceOf(LaravelSanctumClient::class, ApiClient::getClient('mySanctumClient'));
		
		$this->assertFalse(ApiClient::clientExists('myHttpClient'));
		$this->assertInstanceOf(HttpClient::class, ApiClient::getClient('myHttpClient'));
		
		$this->assertFalse(ApiClient::clientExists('myDefaultHttpClient'));
		$this->assertInstanceOf(HttpClient::class, ApiClient::getClient('myDefaultHttpClient'));
		
		$this->assertFalse(ApiClient::clientExists('myBadClient'));
		$this->expectException(InvalidApiClientType::class);
		ApiClient::getClient('myBadClient');
	}
	
	/** @test */
	public function endpoint_url_and_methods_are_determined_correctly()
	{
		config(['apiEndpoints' => [
			'mySanctumClient' => [
				'authentication' => ['type' => 'sanctum'],
				
				'rootUrl' => 'https://localhost:8080/',
				
				'endpoints' => [
					'getTracks' => [
						'url'    => '/tracks/',
						'method' => 'post',
					],
					
					'getPositions' => 'positions',
					
					'sync' => [
						'url' => 'sync/api-credentials/',
						//by default method is 'get', if not provided
					],
					
					'badMethodEndpoint' => [
						'url'    => 'testing',
						'method' => 'bla',
					],
				],
			],
			'myHttpClient' => [
				'authentication' => ['type' => 'http:basic'],
				'endpoints' => [
					'getTracks' => [
						'url'    => 'http://localhost:1516/tracks/',
						'method' => 'post',
					],
					
					'getPositions' => 'http://localhost:1617/positions',
					
					'sync' => [
						'url' => 'http://localhost:1718/sync/api-credentials/',
						//by default method is 'get', if not provided
					],
					
					'badMethodEndpoint' => [
						'url'    => 'http://localhost:1718/sync/api-credentials/',
						'method' => 'bla',
					]
				],
			],
		]]);
		
		$client = ApiClient::getClient('mySanctumClient');
		
		$endpointConfig = $client->getEndpointConfig('getTracks');
		$this->assertIsArray($endpointConfig);
		$this->assertArrayHasKey('url', $endpointConfig);
		$this->assertArrayHasKey('method', $endpointConfig);
		$this->assertEquals('https://localhost:8080/tracks', $endpointConfig['url']);
		$this->assertEquals('post', $endpointConfig['method']);
		
		$endpointConfig = $client->getEndpointConfig('getPositions');
		$this->assertEquals('https://localhost:8080/positions', $endpointConfig['url'] ?? null);
		$this->assertEquals('get', $endpointConfig['method'] ?? null);
		
		$endpointConfig = $client->getEndpointConfig('sync');
		$this->assertEquals('https://localhost:8080/sync/api-credentials', $endpointConfig['url'] ?? null);
		$this->assertEquals('get', $endpointConfig['method'] ?? null);
		
		$client = ApiClient::getClient('myHttpClient');
		
		$endpointConfig = $client->getEndpointConfig('getTracks');
		$this->assertEquals('http://localhost:1516/tracks', $endpointConfig['url'] ?? null);
		$this->assertEquals('post', $endpointConfig['method'] ?? null);
		
		$endpointConfig = $client->getEndpointConfig('getPositions');
		$this->assertEquals('http://localhost:1617/positions', $endpointConfig['url'] ?? null);
		$this->assertEquals('get', $endpointConfig['method'] ?? null);
		
		$endpointConfig = $client->getEndpointConfig('sync');
		$this->assertEquals('http://localhost:1718/sync/api-credentials', $endpointConfig['url'] ?? null);
		$this->assertEquals('get', $endpointConfig['method'] ?? null);
		
		$this->expectException(BadApiEndpointConfig::class);
		$client->getEndpointConfig('badMethodEndpoint');
	}
}