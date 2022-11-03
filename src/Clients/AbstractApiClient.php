<?php

namespace AntonioPrimera\ApiClient\Clients;

use AntonioPrimera\ApiClient\Exceptions\BadApiEndpointConfig;
use AntonioPrimera\ApiClient\Exceptions\MissingApiClientConfig;

abstract class AbstractApiClient
{
	protected $providerName;
	protected $configName;
    protected $timeout;
	
	public function __construct($configName, $providerName)
	{
		$this->configName = $configName;
		$this->providerName = $providerName;
		
		$this->setupClient();
	}
	
	protected function setupClient()
	{
		//override this in your custom client to do any necessary setup
	}
	
	//public function __call(string $name, array $arguments)
	//{
	//	return call_user_func([Http::class, $name], ...$arguments);
	//}
	
	//--- Abstract methods --------------------------------------------------------------------------------------------
	
	//public abstract function callEndpoint(string $endpointName, $data = []);
	
	//public abstract function delete(string $url, array $data = []);
	//public abstract function get(string $url, array|string|null $query = null);
	//public abstract function head(string $url, array|string|null $query = null);
	//public abstract function patch(string $url, array $data = []);
	//public abstract function post(string $url, array $data = []);
	//public abstract function put(string $url, array $data = []);

    //--- Public methods ----------------------------------------------------------------------------------------------
    /**
     * Always returns an array with a valid 'url' and 'method'
     *
     * @param $endpointName
     *
     * @return array
     * @throws BadApiEndpointConfig
     */
    public function getEndpointConfig($endpointName)
    {
        $endpointConfig = $this->getConfig("endpoints.{$endpointName}");
        if (!$endpointConfig)
            throw new MissingApiClientConfig("Missing endpoint config for endpoint {$endpointName} in provider {$this->providerName}");

        $rootUrl = $this->getConfig('rootUrl', '');

        if (is_string($endpointConfig)) {
            return [
                'url'	 => $this->composeUrl($rootUrl, $endpointConfig),
                'method' => 'get',
            ];
        }

        $endpointUrl = $endpointConfig['url'] ?? null;
        if (!$endpointUrl)
            throw new BadApiEndpointConfig("Bad url in api endpoint config for provider {$this->providerName}, endpoint {$endpointName}");

        $endpointMethod = $endpointConfig['method'] ?? 'get';
        if (!in_array($endpointMethod, ['get', 'post', 'patch', 'put', 'head', 'delete']))
            throw new BadApiEndpointConfig("Bad method in api endpoint config for provider {$this->providerName}, endpoint {$endpointName}");

        return [
            'url'    => $this->composeUrl($rootUrl, $endpointUrl),
            'method' => $endpointMethod,
        ];
    }

    public function setTimeout(int $seconds): AbstractApiClient
    {
        $this->timeout = $seconds;

        return $this;
    }

	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	protected function composeUrl(...$parts)
	{
		$trimmedParts = array_filter(array_map(function($part){return trim($part, '/');}, $parts));
		return implode('/', $trimmedParts);
	}
	
	protected function getConfig($configKey, $default = null)
	{
		return config("{$this->configName}.{$this->providerName}.{$configKey}");
	}
}