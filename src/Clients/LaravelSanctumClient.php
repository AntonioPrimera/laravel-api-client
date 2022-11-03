<?php
namespace AntonioPrimera\ApiClient\Clients;

use AntonioPrimera\ApiClient\Exceptions\BadApiEndpointConfig;
use AntonioPrimera\ApiClient\Exceptions\BadAuthenticationType;
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
	
	//--- Magic stuff -------------------------------------------------------------------------------------------------
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
	
	//--- Public methods ----------------------------------------------------------------------------------------------
	
	/**
	 * @throws BadApiEndpointConfig
	 * @return \Illuminate\Http\Client\Response
	 */
	public function callEndpoint(string $endpointName, $data = [])
	{
		$endpointConfig = $this->getEndpointConfig($endpointName);
		return call_user_func([$this, $endpointConfig['method']], $endpointConfig['url'], $data);
	}

    /**
     * Used to specify the maximum number of seconds to wait for a response
     *
     * @throws BadAuthenticationType
     * @throws MissingAuthenticationCredentials
     */
    public function timeout(int $seconds)
    {
        return $this->client()->timeout($seconds);
    }
	
	public function withToken(string $token)
	{
		return $this->setToken($token);
	}
	
	public function setToken(string $token)
	{
		$this->token = $token;
		return $this;
	}
	
	public function getToken()
	{
		return $this->token;
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