<?php

namespace Contributte\Console\Helper;

use Nette\DI\Container;
use Symfony\Component\Console\Helper\Helper;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class ContainerHelper extends Helper
{

	/** @var Container */
	private $container;

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasParameter($key)
	{
		return isset($this->container->parameters[$key]);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getParameter($key)
	{
		if (!$this->hasParameter($key)) {
			return NULL;
		}

		return $this->container->parameters[$key];
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->container->parameters;
	}

	/**
	 * @return Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @param string $type
	 * @return object
	 */
	public function getByType($type)
	{
		return $this->container->getByType($type);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'container';
	}

}
