<?php declare(strict_types = 1);

namespace Contributte\Console\CommandLoader;

use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class ContainerCommandLoader implements CommandLoaderInterface
{

	/**
	 * @param array<string> $commandMap
	 */
	public function __construct(
		private readonly Container $container,
		private readonly array $commandMap,
	)
	{
	}

	public function get(string $name): Command
	{
		if (!$this->has($name)) {
			throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
		}

		return $this->container->getService($this->commandMap[$name]);
	}

	public function has(string $name): bool
	{
		return array_key_exists($name, $this->commandMap);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNames(): array
	{
		return array_keys($this->commandMap);
	}

}
