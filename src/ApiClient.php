<?php
namespace AntonioPrimera\ApiClient;

use AntonioPrimera\ApiClient\Clients\AbstractApiClient;
use AntonioPrimera\ApiClient\Clients\HttpClient;
use AntonioPrimera\ApiClient\Clients\LaravelSanctumClient;
use AntonioPrimera\ApiClient\Exceptions\InvalidApiClientType;
use AntonioPrimera\ApiClient\Exceptions\MissingApiClientConfig;

class ApiClient
{
	protected static $config = 'apiEndpoints';
	protected static $clients = [
		//'provider' => $clientInstance
	];
	
	protected static $clientClasses = [
		'sanctum' 	 => LaravelSanctumClient::class,
		'http:basic' => HttpClient::class,
		'http:query' => HttpClient::class,
		
		//'http:digest' => HttpClient::class,	//todo: implement this
		//'oauth1'  => Oauth1Client::class,		//todo: implement this
		//'oauth2'  => Oauth2Client::class,		//todo: implement this
	];
	
	//--- Client Management -------------------------------------------------------------------------------------------
	
	public static function getClient(string $provider) : AbstractApiClient
	{
		if (!static::clientExists($provider)) {
			static::$clients[$provider] = static::setupClient($provider);
		}
		
		return static::$clients[$provider];
	}
	
	public static function clientExists(string $provider): bool
	{
		return (static::$clients[$provider] ?? null) instanceof AbstractApiClient;
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
		
		$authenticationMethod = static::getConfig("{$providerName}.authentication.type", 'http');
		
		if (!isset(static::$clientClasses[$authenticationMethod]))
			throw new InvalidApiClientType("No client type found for authentication method {$authenticationMethod} in api provider {$providerName}");
		
		return new static::$clientClasses[$authenticationMethod](static::$config, $providerName);
	}
	
	protected static function getConfig($configKey, $default = null)
	{
		return config(static::$config . ".{$configKey}", $default);
	}
}