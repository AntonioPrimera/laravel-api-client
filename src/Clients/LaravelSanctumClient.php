<?php
namespace AntonioPrimera\ApiClient\Clients;

use AntonioPrimera\ApiClient\Exceptions\BadApiEndpointConfig;
use AntonioPrimera\ApiClient\Exceptions\BadHttpMethod;
use AntonioPrimera\ApiClient\Exceptions\MissingAuthenticationCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * @method delete(string $url, array $data = []);
 * @method get(string $url, array|string|null $query = null);
 * @method head(string $url, array|string|null $query = null);
 * @method patch(string $url, array $data = []);
 * @method post(string $url, array $data = []);
 * @method put(string $url, array $data = []);
 */
class LaravelSanctumClient extends AbstractApiClient
{
	protected $token;
	
	//protected function authenticate()
	//{
		//$authenticationEndpoint = $this->getConfig('authentication.endpoint');
		//$credentials = $this->getAuthenticationCredentials();
		//
		//$authenticationResponse = call_user_func(
		//	[Http::class, $authenticationEndpoint['method']],
		//	$authenticationEndpoint['url'],
		//	$credentials
		//);
		///* @var ClientResponse $authenticationResponse */
		//
		//$token = $authenticationResponse->body();
	//}
	
	///**
	// * Get and validate the authentication credentials
	// *
	// * @return array
	// * @throws MissingAuthenticationCredentials
	// */
	//protected function getAuthenticationCredentials()
	//{
	//	$credentials = $this->getConfig('authentication.credentials');
	//
	//	if (!($credentials['email'] ?? null))
	//		throw new MissingAuthenticationCredentials("Missing authentication email for provider {$this->providerName}");
	//
	//	if (!($credentials['password'] ?? null))
	//		throw new MissingAuthenticationCredentials("Missing authentication password for provider {$this->providerName}");
	//
	//	return [
	//		'email'    => $credentials['email'],
	//		'password' => $credentials['password'],
	//	];
	//}
	
	/**
	 * @throws BadHttpMethod
	 * @throws MissingAuthenticationCredentials
	 */
	public function __call(string $name, array $arguments)
	{
		if (!in_array($name, ['get', 'post', 'put', 'patch', 'delete', 'head']))
			throw new BadHttpMethod("Bad api call method: {$name}");
		
		return call_user_func([$this->client(), $name], ...$arguments);
	}
	
	/**
	 * @throws BadApiEndpointConfig
	 */
	public function callEndpoint(string $endpointName, $data = [])
	{
		$endpointConfig = $this->getEndpointConfig($endpointName);
		return call_user_func([$this, $endpointConfig['method']], $endpointConfig['url'], $data);
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	/**
	 * @throws MissingAuthenticationCredentials
	 */
	protected function client(): PendingRequest
	{
		$token = $this->getSanctumToken();
		return Http::withToken($token);
	}
	
	/**
	 * @throws MissingAuthenticationCredentials
	 */
	protected function getSanctumToken()
	{
		if (!$this->token) {
			$this->token = $this->getConfig('authentication.token');
			
			if (!$this->token)
				throw new MissingAuthenticationCredentials("No Sanctum Token found for api provider {$this->providerName}");
		}
		
		
		return $this->token;
	}
}