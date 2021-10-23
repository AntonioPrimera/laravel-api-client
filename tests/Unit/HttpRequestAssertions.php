<?php

namespace AntonioPrimera\ApiClient\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

trait HttpRequestAssertions
{
	
	public function assertRequestMethod($method)
	{
		Http::assertSent(function(Request $request) use ($method) {
			$result = strtolower($request->method()) === strtolower($method);
			if (!$result)
				echo "Expected method {$method} but request had method " . $request->method();
			
			return $result;
		});
		
		return $this;
	}
	
	public function assertRequestUrl($url)
	{
		Http::assertSent(function(Request $request) use ($url) {
			$result = $request->url() === $url;
			if (!$result)
				echo "Expected url [{$url}] but request had url [" . $request->url() . "]";
			
			return $result;
		});
		
		return $this;
	}
	
	public function assertRequestHasHeader($header, $value = null)
	{
		Http::assertSent(function(Request $request) use ($header, $value) {
			$result = $request->hasHeader($header, $value);
			
			if (!$result)
				echo "Request does not have header {$header}" . ($value ? " with value {$value}." : '.');
			
			return $result;
		});
		
		return $this;
	}
	
	public function assertRequestBody($body, $dumpBody = false)
	{
		Http::assertSent(function(Request $request) use ($body, $dumpBody) {
			$result = $request->body() === $body;
			
			if (!$result)
				echo "Request body differs from expected body";
			
			if ($dumpBody)
				dump($request->body());
			
			return $result;
		});
		
		return $this;
	}
	
	public function assertHasBearerToken($token)
	{
		Http::assertSent(function(Request $request) use ($token) {
			$result = $request->hasHeader('Authorization', "Bearer {$token}");
			
			if (!$result)
				echo "Request does not have the expected bearer token authorization header";
			
			return $result;
		});
		
		return $this;
	}
	
	public function assertHasBasicAuthentication($username, $password)
	{
		Http::assertSent(function(Request $request) use ($password, $username) {
			$result = $request->hasHeader('Authorization', 'Basic ' . base64_encode("{$username}:{$password}"));
			
			if (!$result)
				echo "Request does not have the expected basic authorization header";
			
			return $result;
		});
		
		return $this;
	}
	
	public function dumpRequest()
	{
		Http::assertSent(function(Request $request) {
			dump($request);
		});
	}
}