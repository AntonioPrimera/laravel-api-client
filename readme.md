# Laravel Api Client

## Installation

Install the pacakage via composer

`composer require antonioprimera/laravel-api-client`

If you want to use pre-configured clients and endpoints, rather than define authentication types,
credentials, request methods and urls as part of your php code, you can create a new config file,
named **apiEndpoints.php**.

## Usage

The ApiClient can be used either based on a config file or by just creating and specifying all necessary
authentication details in the code.

#### Example Usage without config

Create a Laravel Sanctum client. The Laravel Sanctum client will send its authentication token in the
Authorization header, as the Bearer token.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;
    
    $response = ApiClient::makeSanctumClient()
        ->withToken('some-token')
        ->post('http://my-api-endpoint-url.com', ['id' => 15]);

```

Create a http client with basic authentication. This type of authentication requires the credentials
to be set as an array with the **username** and **password** keys. These credentials will be encoded
and sent in the Authorization header.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;

    $response = ApiClient::makeHttpClient()
        ->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_BASIC)
        ->withCredentials(['username' => 'my-user-name', 'password' => 'my-password'])
        ->get('http://my-api-endpoint-url.com', ['id' => 15]);
```

Create a http client with query authentication. For this type of authentication, the credentials are
sent via query parameters in the url for get request or via the request body, for other request methods.
You can provide any set of authentication data (not necessary username and password, like in the basic
http authentication protocol).

The example below will make a get request to:
`http://my-api-endpoint-url.com?user=my-user-name&pass=my-pass&tk=my-token&id=15`

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;

    $response = ApiClient::makeHttpClient()
        ->withAuthenticationType(HttpClient::AUTHENTICATION_TYPE_QUERY)
        ->withCredentials(['user' => 'my-user-name', 'pass' => 'my-pass', 'tk' => 'my-token'])
        ->get('http://my-api-endpoint-url.com', ['id' => 15]);
```

#### Example Usage with config (see sample config below)

Create a laravel sanctum client based on the configured client 'mySanctumClient' and call a configured
endpoint. If the api token is provided in the config, you can just call a configured endpoint or
some other url. If the token is not available in the config, you must use the ***withCredentials(...)***
or ***setCredentials()*** method, before calling any endpoint / url.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;
    
    $response = ApiClient::getClient('mySanctumClient')
        ->callEndpoint('setTracks', ['tracks' => '...']);
```

Create a http client with basic authentication. If the credentials are provided in the config, you can
just call a configured endpoint or some other url. If the credentials are not available in the config,
you must use the ***withCredentials(...)*** method or the ***setCredentials()***, before calling the
endpoint.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;
 
    $response = ApiClient::makeClient('myBasicHttpClientWithCredentials')
        ->callEndpoint('getUser', ['user-id' => 15]);

```

Create a http client with query authentication. For this type of authentication, the credentials are
sent via query parameters in the url for get request or via the request body, for other request methods.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;
    
    $response = ApiClient::getClient('myQueryHttpClient')
        ->callEndpoint('getMenu');
        
```

You can use a configured client to make calls to endpoints with a given url, so it's not mandatory
to configure all endpoints. Just call the 'get' / 'post' / 'put' / 'patch' / 'delete' / 'head' method
on the client and provide the necessary data.

```php
    use AntonioPrimera\ApiClient\ApiClient;
    use AntonioPrimera\ApiClient\Clients\HttpClient;
    
    $response = ApiClient::getClient('myQueryHttpClient')
        ->post('http://my-api-endpoint-url.com', ['user' => ['id' => 15, 'name' => 'Gigi']]);
```

## Config

By default, the config file **apiEndpoints.php** is used, so don't forget to create it if you want to
use the api client based on config data.

If you want to use another config file, or to change the behavior of the ***ApiClient*** class, you must
create your own ApiClient class in your project, inheriting the ***AntonioPrimera\ApiClient\ApiClient***.
Then you can override the static variable ***$config*** to point to your desider config file

```php
use AntonioPrimera\ApiClient\ApiClient;

class MyApiClient extends ApiClient
{
    protected static $config = 'myApiConfig';
}
```

The Api client can be used also without a config, by specifying the http client type, the authentication
type, the credentials, the url and the method to be used to make the request.

```php

//sample config
return [

    //the name of the client, usually the name of an external api provider e.g. "github" / "instagram"
    'mySanctumClient' => [
        
        //mandatory to have at least the authentication type provided
        'authentication' => [
            'type'  => 'sanctum',
            
            //(optional) if not provided, must be provided at run-time
            'token' => env('MY_SANCTUM_TOKEN'),
        ],
        
        //(optional) if rootUrl is provided it will be prepended to each endpoint url
        'rootUrl' => 'https://localhost:8080/',
        
        //the list of all endpoints
        'endpoints' => [
        
            'setTracks' => [                //endpoint name, to be used in development (like a route name)
                'url'    => '/tracks/',     //url is mandatory
                'method' => 'post',         //(optional) by default: 'get'
            ],
            
            //endpoints with method 'get' can also be provided as strings
            'getPositions' => 'positions',
        ],
    ],
    
    //example of a provider with a basic http authentication
    'myBasicHttpClientWithCredentials' => [
        'authentication' => [
            'type' => 'http:basic',
            
            //optional
            'credentials' => [
                'username' => env('MY_HTTP_CLIENT_USERNAME'),
                'password' => env('MY_HTTP_CLIENT_PASSWORD'),
            ],
        ],
        
        'endpoints' => [
            //... all endpoints are the same, regardless of the authentication type
        ],
    ],
    
    //example of a provider with an authentication via query parameters (credentials are sent as part of the url)
    'myQueryHttpClient' => [
        'authentication' => [
            'type' => 'http:query',
            
            //credentials are optional (can be provided at runtime via method $client->setCredentials(...)
            'credentials' => [
                'key' 		 => 'my-key',
                'passphrase' => 'my-phrase',
                'token'		 => 'my-token',
            ],

        ],
        
        'endpoints' => [
            //... all endpoints are the same, regardless of the authentication type
        ],
    ],    
];

```