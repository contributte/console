<?php declare(strict_types = 1);

namespace Contributte\Console\DI;

use Nette\Http\Request;
use Nette\Http\UrlScript;

class RequestFactory extends \Nette\Http\RequestFactory
{

	/** @var string */
	private $url;

	public function __construct(string $url)
	{
		$this->url = $url;
	}


	public function fromGlobals(): Request
	{
		return new Request(new UrlScript($this->url));
	}

}
