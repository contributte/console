<?php declare(strict_types = 1);

namespace Contributte\Console\CommandLoader;

use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class ContainerCommandLoader implements CommandLoaderInterface
{

	/** @var Container */
	private $container;

	/** @var string[] */
	private $commandMap;

	/**
	 * @param string[] $commandMap
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
	 * @throws CommandNotFoundException
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function get($name): Command
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
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function has($name): bool
	{
		return array_key_exists($name, $this->commandMap);
	}

	/**
	 * @return string[] All registered command names
	 */
	public function getNames(): array
	{
		return array_keys($this->commandMap);
	}

}
