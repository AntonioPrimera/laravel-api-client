<?php
namespace AntonioPrimera\ApiClient;

use AntonioPrimera\ApiClient\Clients\AbstractApiClient;
use AntonioPrimera\ApiClient\Clients\HttpClient;
use AntonioPrimera\ApiClient\Clients\LaravelSanctumClient;
use AntonioPrimera\ApiClient\Exceptions\InvalidApiClientType;
use AntonioPrimera\ApiClient\Exceptions\MissingApiClientConfig;

/**
 * @method \Illuminate\Http\Client\Response callEndpoint(string $endpointName, $data = [])
 * @method \Illuminate\Http\Client\PendingRequest timeout(int $seconds);
 */
class ApiClient
{
	protected static $config = 'apiEndpoints';
	protected static $clients = [
		//'provider' => $clientInstance
	];
	
	protected static $clientClasses = [
		'bearer'	 => LaravelSanctumClient::class,
		'sanctum' 	 => LaravelSanctumClient::class,
		'http:basic' => HttpClient::class,
		'http:query' => HttpClient::class,
		
		//'http:digest' => HttpClient::class,	//todo: implement this
		//'oauth1'  => Oauth1Client::class,		//todo: implement this
		//'oauth2'  => Oauth2Client::class,		//todo: implement this
	];
	
	//--- Client Management -------------------------------------------------------------------------------------------
	
	/**
	 * Get an existing api client for the given provider
	 * name or create a new one if it doesn't exist
	 *
	 * @throws InvalidApiClientType
	 * @throws MissingApiClientConfig
	 */
	public static function getClient(string $providerName) : AbstractApiClient
	{
		if (!static::clientExists($providerName)) {
			static::addClient($providerName, static::setupClient($providerName));
		}
		
		return static::$clients[$providerName];
	}
	
	/**
	 * Create a new client, even if one already exists
	 *
	 * @throws InvalidApiClientType
	 * @throws MissingApiClientConfig
	 */
	public static function makeClient(string $providerName) : AbstractApiClient
	{
		static::addClient($providerName, static::setupClient($providerName));
		
		return static::$clients[$providerName];
	}
	
	public static function clientExists(string $providerName): bool
	{
		return (static::$clients[$providerName] ?? null) instanceof AbstractApiClient;
	}
	
	public static function makeSanctumClient(?string $providerName = null, ?string $token = null)
	{
		$client = new LaravelSanctumClient(static::$config, $providerName);
		
		if ($token)
			$client->setToken($token);
		
		//if a provider name was given, save the client
		if ($providerName)
			static::addClient($providerName, $client);
			
		return $client;
	}
	
	public static function makeHttpClient(?string $providerName = null, ?string $authenticationType = null, ?array $credentials = [])
	{
		$client = new HttpClient(static::$config, $providerName);
		
		if ($authenticationType)
			$client->setAuthenticationType($authenticationType);
		
		if ($credentials)
			$client->setCredentials($credentials);
		
		//if a provider name was given, save the client
		if ($providerName)
			static::addClient($providerName, $client);
		
		return $client;
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	/**
	 * @throws MissingApiClientConfig
	 * @throws InvalidApiClientType
	 */
	protected static function setupClient(string $providerName)
	{
		$providerConfig = static::getConfig($providerName);
		
		if (!$providerConfig)
			throw new MissingApiClientConfig("Missing config for api provider {$providerName}");
		
		$authenticationMethod = static::getConfig("{$providerName}.authentication.type", 'http:basic');
		
		if (!isset(static::$clientClasses[$authenticationMethod]))
			throw new InvalidApiClientType("No client type found for authentication method {$authenticationMethod} in api provider {$providerName}");
		
		$client = new static::$clientClasses[$authenticationMethod](static::$config, $providerName);
		
		if ($authenticationMethod === 'http:basic')
			$client->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_BASIC);
		
		if ($authenticationMethod === 'http:query')
			$client->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_QUERY);
		
		return $client;
	}
	
	protected static function getConfig($configKey, $default = null)
	{
		return config(static::$config . ".{$configKey}", $default);
	}
	
	protected static function addClient(string $providerName, AbstractApiClient $client)
	{
		static::$clients[$providerName] = $client;
	}
}