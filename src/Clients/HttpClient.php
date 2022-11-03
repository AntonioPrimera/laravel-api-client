<?php
namespace AntonioPrimera\ApiClient\Clients;

use AntonioPrimera\ApiClient\Exceptions\BadApiEndpointConfig;
use AntonioPrimera\ApiClient\Exceptions\BadAuthenticationType;
use AntonioPrimera\ApiClient\Exceptions\BadHttpMethod;
use AntonioPrimera\ApiClient\Exceptions\BadRequestUrlException;
use AntonioPrimera\ApiClient\Exceptions\MissingAuthenticationCredentials;
use Illuminate\Support\Facades\Http;

class HttpClient extends AbstractApiClient
{
	const AUTHENTICATION_TYPE_BASIC = 'basic';
	const AUTHENTICATION_TYPE_QUERY = 'query';
	
	protected $authenticationType;
	protected $credentials;
	protected $requestData = [];
	
	//--- Client setup ------------------------------------------------------------------------------------------------
	
	protected function setupClient()
	{
		if (!$this->authenticationType)
			$this->authenticationType = static::AUTHENTICATION_TYPE_BASIC;
	}
	
	//--- Magic stuff -------------------------------------------------------------------------------------------------
	
	/**
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return false|mixed
	 * @throws BadAuthenticationType
	 * @throws BadHttpMethod
	 * @throws MissingAuthenticationCredentials
	 * @throws BadRequestUrlException
	 */
	public function __call(string $name, array $arguments)
	{
		if (!in_array($name, ['get', 'post', 'put', 'patch', 'delete', 'head']))
			throw new BadHttpMethod("Bad api call method: {$name}");
	
		return $this->sendRequest($name, $arguments[0] ?? '', $arguments[1]);
	}
	
	//--- Public methods ----------------------------------------------------------------------------------------------
	
	/**
	 * @param string $endpointName
	 * @param array  $data
	 *
	 * @return \Illuminate\Http\Client\Response
	 * @throws BadApiEndpointConfig
	 * @throws BadAuthenticationType
	 * @throws BadRequestUrlException
	 * @throws MissingAuthenticationCredentials
	 */
	public function callEndpoint(string $endpointName, $data = [])
	{
		$endpointConfig = $this->getEndpointConfig($endpointName);
		return $this->sendRequest($endpointConfig['method'], $endpointConfig['url'], $data);
	}
	
	//--- Getters and Setters -----------------------------------------------------------------------------------------
	
	/**
	 * @return array
	 */
	public function getRequestData(): array
	{
		return $this->requestData;
	}
	
	/**
	 * @param array $parameters
	 *
	 * @return HttpClient
	 */
	public function setRequestData(array $parameters): HttpClient
	{
		$this->requestData = $parameters;
		
		return $this;
	}
	
	public function withData(array $requestData)
	{
		return $this->setRequestData(array_merge($this->getRequestData(), $requestData));
	}
	
	public function setAuthenticationType($type)
	{
		//cleanup authentication type (e.g. transform "http:basic" into "basic")
		$cleanType = str_ireplace('http:', '', $type);
		
		$this->authenticationType = $cleanType;
		return $this;
	}
	
	public function getAuthenticationType()
	{
		if (!$this->authenticationType)
			$this->setAuthenticationType($this->getAuthenticationTypeFromConfig());
		
		return $this->authenticationType;
	}
	
	public function withAuthenticationType($type)
	{
		return $this->setAuthenticationType($type);
	}
	
	/**
	 * @param mixed $credentials
	 *
	 * @return HttpClient
	 */
	public function setCredentials(array $credentials)
	{
		$this->credentials = $credentials;
		return $this;
	}
	
	public function withCredentials(array $credentials)
	{
		return $this->setCredentials($credentials);
	}
	
	/**
	 * @return array | null
	 */
	public function getCredentials()
	{
		if (!$this->credentials)
			$this->credentials = $this->getAuthenticationCredentialsFromConfig();
		
		return $this->credentials;
	}
	
	/**
	 * Get and validate the authentication credentials
	 *
	 * @return array
	 */
	protected function getAuthenticationCredentialsFromConfig()
	{
		return $this->getConfig('authentication.credentials', []);
	}
	
	/**
	 * @return string
	 */
	protected function getAuthenticationTypeFromConfig()
	{
		return $this->getConfig('authentication.type', static::AUTHENTICATION_TYPE_BASIC);
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	/**
	 * @return \Illuminate\Http\Client\Response
	 * @throws BadRequestUrlException
	 * @throws MissingAuthenticationCredentials
	 * @throws BadAuthenticationType
	 */
	protected function sendRequest(string $method, string $url, array $data = [])
	{
		if (!$url)
			throw new BadRequestUrlException('No Url for Http client request');
		
		return call_user_func([$this->client(), $method], $url, array_merge($this->getRequestData(), $data));
	}
	
	protected function client()
	{
		//ApiClient::getClient('vipas')->withAuthenticationType('basic')->withCredentials(['username' => '...', 'password' => '...'])->callEndpoint('dayalvunit', ['from' => '']);
		$credentials = $this->getCredentials();
		$authenticationType = $this->getAuthenticationType();
		
		//create a client with basic authentication
		if ($authenticationType === static::AUTHENTICATION_TYPE_BASIC) {
			if (!($credentials['username'] ?? null) || !($credentials['password'] ?? null))
				throw new MissingAuthenticationCredentials('Invalid authentication credentials for http client with basic authentication');
			
			return Http::withBasicAuth($credentials['username'], $credentials['password']);
		}
		
		//create a client with authentication parameters sent via query
		if ($authenticationType === static::AUTHENTICATION_TYPE_QUERY) {
			if (!($credentials && is_array($credentials)))
				throw new MissingAuthenticationCredentials('Invalid authentication credentials for http client with query authentication');
			
			$this->withData($credentials);
			
			return Http::class;
		}
		
		throw new BadAuthenticationType("Invalid authentication type {$authenticationType} for Http Client.");
	}
}