<?php
namespace AntonioPrimera\ApiClient\Tests\Unit;

use AntonioPrimera\ApiClient\ApiClient;
use AntonioPrimera\ApiClient\Clients\HttpClient;
use AntonioPrimera\ApiClient\Clients\LaravelSanctumClient;
use AntonioPrimera\ApiClient\Exceptions\MissingApiClientConfig;
use AntonioPrimera\ApiClient\Exceptions\MissingAuthenticationCredentials;
use AntonioPrimera\ApiClient\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class HttpClientConfigTest extends TestCase
{
	use HttpRequestAssertions;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		Http::fake([
			'*' => Http::response('Success'),
		]);
		
		config(['apiEndpoints' => $this->getConfig()]);
	}
	
	/** @test */
	public function a_simple_sanctum_client_can_be_created_from_config()
	{
		$client = ApiClient::makeClient('mySanctumClient');
		/* @var LaravelSanctumClient $client */
		
		$this->assertInstanceOf(LaravelSanctumClient::class, $client);
		$client->callEndpoint('getTracks', ['id' => 15]);
		
		$this->assertRequestMethod('post')
			->assertRequestUrl('https://localhost:8080/tracks')
			->assertHasBearerToken('my-token-123')
			->assertRequestBody('{"id":15}');
		
		ApiClient::getClient('mySanctumClient')->callEndpoint('getPositions');
		$this->assertRequestMethod('get')
			->assertRequestUrl('https://localhost:8080/positions')
			->assertHasBearerToken('my-token-123');
	}
	
	/** @test */
	public function a_sanctum_client_with_no_configured_token_can_be_created()
	{
		$client = ApiClient::makeClient('mySanctumClientNoToken');
		/* @var LaravelSanctumClient $client */
		
		$this->assertInstanceOf(LaravelSanctumClient::class, $client);
		$this->assertNull($client->getToken());
		
		//by setting the token it should make the call successfully
		$client->withToken('some-token')->callEndpoint('syncData');
		$this->assertRequestMethod('get')
			->assertRequestUrl('https://localhost:8080/sync')
			->assertHasBearerToken('some-token');

		//uses last set token
		ApiClient::getClient('mySanctumClientNoToken')->callEndpoint('refresh');
		$this->assertRequestMethod('get')
			->assertRequestUrl('https://localhost:8080/refresh')
			->assertHasBearerToken('some-token');
	}
	
	/** @test */
	public function an_exception_is_thrown_if_no_bearer_token_is_configured_or_given()
	{
		$this->expectException(MissingAuthenticationCredentials::class);
		ApiClient::makeClient('mySanctumClientNoToken')->callEndpoint('syncData');
	}
	
	/** @test */
	public function a_configured_http_client_with_basic_authentication_can_be_created_and_used()
	{
		$client = ApiClient::getClient('myBasicHttpClientWithCredentials');
		/* @var HttpClient $client */
		
		$client->callEndpoint('syncMasterData');
		$this->assertRequestMethod('post')
			->assertRequestUrl('http://localhost:1718/sync/api-credentials')
			->assertHasBasicAuthentication('me', 'my-pass');
	}
	
	/** @test */
	public function a_configured_basic_http_client_with_no_configured_credentials_can_be_used()
	{
		$client = ApiClient::getClient('myBasicHttpClient');
		/* @var HttpClient $client */
		
		$client->withCredentials(['username' => 'me-too', 'password' => 'my-pass-too'])
			->callEndpoint('syncMasterData');
		
		$this->assertRequestMethod('get')
			->assertRequestUrl('http://localhost:1718/sync/api-credentials')
			->assertHasBasicAuthentication('me-too', 'my-pass-too');
	}
	
	/** @test */
	public function an_exception_is_thrown_if_no_credentials_are_configured_or_given()
	{
		$this->expectException(MissingAuthenticationCredentials::class);
		ApiClient::makeClient('myBasicHttpClient')->callEndpoint('syncMasterData');
	}
	
	/** @test */
	public function an_exception_is_thrown_if_endpoint_config_is_not_found()
	{
		$this->expectException(MissingApiClientConfig::class);
		ApiClient::makeClient('myBasicHttpClient')->callEndpoint('nonExistingEndpoint');
	}
	
	/** @test */
	public function a_configured_http_client_with_query_authentication_can_be_created_and_used()
	{
		$client = ApiClient::getClient('myQueryHttpClientWithCredentials');
		/* @var HttpClient $client */
		
		$client->callEndpoint('syncMasterData');
		$this->assertRequestMethod('get')
			->assertRequestUrl('http://localhost:1718/sync/api-credentials?key=my-key&passphrase=my-phrase&token=my-token');
	}
	
	/** @test */
	public function a_configured_query_http_client_with_no_configured_credentials_can_be_used()
	{
		$client = ApiClient::getClient('myQueryHttpClient');
		/* @var HttpClient $client */
		
		$client->withCredentials(['auth-token' => 'some-token'])
			->callEndpoint('syncMasterData');
		
		$this->assertRequestMethod('get')
			->assertRequestUrl('http://localhost:1718/sync/api-credentials?auth-token=some-token');
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	protected function getConfig()
	{
		return [
			'mySanctumClient' => [
				'authentication' => [
					'type'  => 'sanctum',
					'token' => 'my-token-123',
				],
				
				'rootUrl' => 'https://localhost:8080/',
				
				'endpoints' => [
					'getTracks' => [
						'url'    => '/tracks/',
						'method' => 'post',
					],
					
					'getPositions' => 'positions',
				],
			],
			
			'mySanctumClientNoToken' => [
				'authentication' => [
					'type'  => 'sanctum',
				],
				
				'endpoints' => [
					'syncData' => 'https://localhost:8080/sync/',
					'refresh'  => 'https://localhost:8080/refresh/',
				],
			],
			
			'myBasicHttpClientWithCredentials' => [
				'authentication' => [
					'type' => 'http:basic',
					
					'credentials' => [
						'username' => 'me',
						'password' => 'my-pass',
					],
				],
				
				'endpoints' => [
					'syncMasterData' => [
						'url' => 'http://localhost:1718/sync/api-credentials/',
						'method' => 'post',
					],
				],
			],
			
			'myBasicHttpClient' => [
				'authentication' => [
					'type' => 'http:basic',
				],
				
				'endpoints' => [
					'syncMasterData' => [
						'url' => 'http://localhost:1718/sync/api-credentials/',
					],
				],
			],
			
			'myQueryHttpClient' => [
				'authentication' => [
					'type' => 'http:query',
				],
				
				'endpoints' => [
					'syncMasterData' => [
						'url' => 'http://localhost:1718/sync/api-credentials/',
					],
				],
			],
			
			'myQueryHttpClientWithCredentials' => [
				'authentication' => [
					'type' => 'http:query',
					
					'credentials' => [
						'key' 		 => 'my-key',
						'passphrase' => 'my-phrase',
						'token'		 => 'my-token',
					],
				],
				
				'endpoints' => [
					'syncMasterData' => 'http://localhost:1718/sync/api-credentials/',
				],
			],
		];
	}
	
	
}