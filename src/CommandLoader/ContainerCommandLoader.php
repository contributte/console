<?php

namespace Contributte\Console\CommandLoader;

use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Nette\DI\Container;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class ContainerCommandLoader implements CommandLoaderInterface
{

	/** @var Container */
	private $container;

	/** @var array */
	private $commandMap = [];

	/**
	 * @param Container $container
	 * @param array $commandMap
	 */
	public function __construct(Container $container, array $commandMap)
	{
		$this->container = $container;
		$this->commandMap = $commandMap;
	}

	/**
	 * Loads a command.
	 *
	 * @param string $name
	 * @return object
	 * @throws CommandNotFoundException
	 */
	public function get($name)
	{
		if (!$this->has($name)) {
			throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
		}

		return $this->container->getService($this->commandMap[$name]);
	}

	/**
	 * Checks if a command exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function has($name)
	{
		return array_key_exists($name, $this->commandMap);
	}

	/**
	 * @return string[] All registered command names
	 */
	public function getNames()
	{
		return array_keys($this->commandMap);
	}

}
