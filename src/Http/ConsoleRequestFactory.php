<?php declare(strict_types = 1);

namespace Contributte\Console\Http;

use Nette\Http\Request;
use Nette\Http\RequestFactory;
use Nette\Http\UrlScript;

class ConsoleRequestFactory extends RequestFactory
{

	public function __construct(
		private readonly string $url,
	)
	{
	}

	public function fromGlobals(): Request
	{
		return new Request(new UrlScript($this->url));
	}

	public function createHttpRequest(): Request
	{
		return $this->fromGlobals();
	}

}
