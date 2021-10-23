<?php
namespace AntonioPrimera\ApiClient\Tests\Unit;

use AntonioPrimera\ApiClient\ApiClient;
use AntonioPrimera\ApiClient\Clients\HttpClient;
use AntonioPrimera\ApiClient\Clients\LaravelSanctumClient;
use AntonioPrimera\ApiClient\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class HttpClientTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		
		Http::fake([
			'*' => Http::response('Success'),
		]);
	}
	
	/** @test */
	public function a_simple_sanctum_client_can_be_created()
	{
		$client = ApiClient::makeSanctumClient('mySanctumApi', 'token-123');
		
		$this->assertInstanceOf(LaravelSanctumClient::class, $client);
		
		$this->assertEquals('token-123', $client->getToken());
	}
	
	/** @test */
	public function a_simple_http_client_can_be_created()
	{
		$client = ApiClient::makeHttpClient('myBasicHttpApi');
		$this->assertInstanceOf(HttpClient::class, $client);
		
		//default authentication type
		$this->assertEquals(HttpClient::AUTHENTICATION_TYPE_BASIC, $client->getAuthenticationType());
		
		$credentials = [
			'username' => 'me',
			'password' => 'my-pass'
		];
		$client = ApiClient::makeHttpClient(
		'myBasicHttpApi',
		HttpClient::AUTHENTICATION_TYPE_QUERY,
			$credentials
		);
		
		$this->assertEquals(HttpClient::AUTHENTICATION_TYPE_QUERY, $client->getAuthenticationType());
		$this->assertEquals($credentials, $client->getCredentials());
	}
	
	/** @test */
	public function a_newly_created_client_will_be_saved_and_can_be_retrieved_later_by_its_provider_name()
	{
		$client = ApiClient::makeSanctumClient('mySanctumApi', 'token-123');
		$this->assertTrue($client === ApiClient::getClient('mySanctumApi'));
		
		$client = ApiClient::makeHttpClient('myBasicHttpApi');
		$this->assertTrue($client === ApiClient::getClient('myBasicHttpApi'));
	}
	
	/** @test */
	public function a_sanctum_client_will_make_a_valid_request_using_the_bearer_token()
	{
		$client = ApiClient::makeSanctumClient('mySanctumApi', 'token-123');
		$client->get('http://localhost:8080/api/endpoint', ['id' => 15]);
		
		Http::assertSent(function(Request $request) {
			return $request->hasHeader('Authorization', 'Bearer token-123')
				&& $request->url() === 'http://localhost:8080/api/endpoint?id=15'
				&& strtolower($request->method()) === 'get';
		});
	}
	
	/** @test */
	public function an_http_client_with_basic_authentication_will_make_a_valid_request()
	{
		$client = ApiClient::makeHttpClient();
		$client->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_BASIC)
			->withCredentials(['username' => 'me', 'password' => 'my-pass'])
			->get('http://localhost:8080/api/endpoint', ['id' => 15]);
		
		Http::assertSent(function(Request $request) {
			return $request->hasHeader('Authorization', 'Basic ' . base64_encode('me:my-pass'))
				&& $request->url() === 'http://localhost:8080/api/endpoint?id=15'
				&& strtolower($request->method()) === 'get';
		});
	}
	
	/** @test */
	public function an_http_client_with_query_authentication_will_make_a_valid_request()
	{
		$client = ApiClient::makeHttpClient();
		$client->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_QUERY)
			->withCredentials(['username' => 'me', 'password' => 'my-pass'])
			->get('http://localhost:8080/api/endpoint', ['id' => 15]);
		
		Http::assertSent(function(Request $request) {
			return !$request->hasHeader('Authorization')
				&& $request->url() === 'http://localhost:8080/api/endpoint?username=me&password=my-pass&id=15'
				&& strtolower($request->method()) === 'get';
		});
	}
}