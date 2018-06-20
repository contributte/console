<?php declare(strict_types = 1);

namespace Contributte\Console\Helper;

use Nette\DI\Container;
use Symfony\Component\Console\Helper\Helper;

/**
 * @deprecated
 */
class ContainerHelper extends Helper
{

	/** @var Container */
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function hasParameter(string $key): bool
	{
		return isset($this->container->parameters[$key]);
	}

	/**
	 * @return mixed
	 */
	public function getParameter(string $key)
	{
		if (!$this->hasParameter($key)) {
			return null;
		}

		return $this->container->parameters[$key];
	}

	/**
	 * @return mixed[]
	 */
	public function getParameters(): array
	{
		return $this->container->parameters;
	}

	public function getContainer(): Container
	{
		return $this->container;
	}

	/**
	 * @return object
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	public function getByType(string $type)
	{
		return $this->container->getByType($type);
	}

	public function getName(): string
	{
		return 'container';
	}

}
