<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Nette\Http\Request;
use Nette\Http\RequestFactory;
use Nette\Http\UrlScript;

class FooRequestFactory extends RequestFactory
{

	public const CUSTOM_URL = 'http://custom/';

	public function fromGlobals(): Request
	{
		return new Request(new UrlScript(self::CUSTOM_URL));
	}

	public function createHttpRequest(): Request
	{
		return $this->fromGlobals();
	}

}
