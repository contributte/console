<?php declare(strict_types = 1);

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Http\ConsoleRequestFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceCreationException;
use Nette\Http\RequestFactory;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Arrays;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method stdClass getConfig()
 */
class ConsoleExtension extends CompilerExtension
{

	public function __construct(
		private readonly bool $cliMode = false,
	)
	{
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'url' => Expect::anyOf(Expect::string(), Expect::null())->dynamic(),
			'name' => Expect::string()->dynamic(),
			'version' => Expect::anyOf(Expect::string(), Expect::int(), Expect::float()),
			'catchExceptions' => Expect::bool()->dynamic(),
			'autoExit' => Expect::bool(),
			'helperSet' => Expect::anyOf(Expect::string(), Expect::type(Statement::class)),
			'helpers' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Skip if isn't CLI
		if ($this->cliMode !== true) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Register Symfony Console Application
		$applicationDef = $builder->addDefinition($this->prefix('application'))
			->setFactory(Application::class);

		// Setup console name
		if ($config->name !== null) {
			$applicationDef->addSetup('setName', [$config->name]);
		}

		// Setup console version
		if ($config->version !== null) {
			$applicationDef->addSetup('setVersion', [(string) $config->version]);
		}

		// Catch or populate exceptions
		if ($config->catchExceptions !== null) {
			$applicationDef->addSetup('setCatchExceptions', [$config->catchExceptions]);
		}

		// Call die() or not
		if ($config->autoExit !== null) {
			$applicationDef->addSetup('setAutoExit', [$config->autoExit]);
		}

		// Register given or default HelperSet
		if ($config->helperSet !== null) {
			$applicationDef->addSetup('setHelperSet', [new Statement($config->helperSet)]);
		}

		// Register extra helpers
		foreach ($config->helpers as $helperName => $helperConfig) {
			$helperDef = $builder->addDefinition($this->prefix('helper.' . $helperName))
				->setFactory(new Statement($helperConfig))
				->setAutowired(false);

			$applicationDef->addSetup('?->getHelperSet()->set(?)', ['@self', $helperDef]);
		}

		// Commands lazy loading
		$builder->addDefinition($this->prefix('commandLoader'))
			->setType(CommandLoaderInterface::class)
			->setFactory(ContainerCommandLoader::class);

		$applicationDef->addSetup('setCommandLoader', ['@' . $this->prefix('commandLoader')]);

		// Export types
		$this->compiler->addExportedType(Application::class);
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Skip if isn't CLI
		if ($this->cliMode !== true) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		/** @var ServiceDefinition $applicationDef */
		$applicationDef = $builder->getDefinition($this->prefix('application'));

		// Setup URL for CLI
		if ($config->url !== null && $builder->hasDefinition('http.requestFactory')) {
			$httpDef = $builder->getDefinition('http.requestFactory');
			assert($httpDef instanceof ServiceDefinition);
			$factoryEntity = $httpDef->getFactory()->getEntity();
			if ($factoryEntity === RequestFactory::class) {
				$httpDef->setFactory(ConsoleRequestFactory::class, [$config->url]);
			} else {
				throw new ServiceCreationException(
					'Custom http.requestFactory is used, argument console.url should be removed.'
				);
			}
		}

		// Add all commands to map for command loader
		$commands = $builder->findByType(Command::class);
		$commandMap = [];

		// Iterate over all commands and build commandMap
		foreach ($commands as $serviceName => $service) {
			$tags = $service->getTags();
			$commandName = null;

			// Try to use console.command tag
			if (isset($tags['console.command'])) {
				if (is_string($tags['console.command'])) {
					$commandName = $tags['console.command'];
				} elseif (is_array($tags['console.command'])) {
					$commandName = Arrays::get($tags['console.command'], 'name', null);
				}
			}

			$aliases = [];
			// Try to detect command name from Command::getDefaultName() or Command::defaultName property
			if (!is_string($commandName) || $commandName === '') {
				/** @var class-string $className */
				$className = $service->getType();
				$reflection = new ReflectionClass($className);
				$attributes = $reflection->getAttributes(AsCommand::class);

				if ($attributes !== []) {
					$commandName = $attributes[0]->newInstance()->name;
				} elseif (method_exists($className, 'getDefaultName')) {
					$commandName = call_user_func([$service->getType(), 'getDefaultName']); // @phpstan-ignore-line
				} elseif (property_exists($className, 'defaultName')) {
					$rp = new ReflectionProperty($className, 'defaultName');
					$commandName = $rp->getValue();
				}

				if (!is_string($commandName) || $commandName === '') {
					throw new ServiceCreationException(
						sprintf(
							'Command "%s" missing #[AsCommand] attribute',
							$service->getType(),
						)
					);
				}

				$aliases = explode('|', $commandName);
				$commandName = array_shift($aliases);
				if ($commandName === '') {
					$commandName = array_shift($aliases);
				}
			}

			// Append service to command map
			$commandMap[$commandName] = $serviceName;

			foreach ($aliases as $alias) {
				$commandMap[$alias] = $serviceName;
			}
		}

		/** @var ServiceDefinition $commandLoaderDef */
		$commandLoaderDef = $builder->getDefinition($this->prefix('commandLoader'));
		$commandLoaderDef->setArguments(['@container', $commandMap]);

		// Register event dispatcher, if available
		try {
			$dispatcherDef = $builder->getDefinitionByType(EventDispatcherInterface::class);
			$applicationDef->addSetup('setDispatcher', [$dispatcherDef]);
		} catch (MissingServiceException $e) {
			// Event dispatcher is not installed, ignore
		}
	}

}
