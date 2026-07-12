<?php declare(strict_types = 1);

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Http\ConsoleRequestFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceCreationException;
use Nette\Http\RequestFactory;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
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
			'version' => Expect::anyOf(Expect::string(), Expect::int(), Expect::float())->dynamic(),
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
			if (!$config->version instanceof Statement) {
				$config->version = (string) $config->version;
			}

			$applicationDef->addSetup('setVersion', [$config->version]);
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
		$commandMap = [];

		foreach ($builder->getDefinitions() as $service) {
			$commandMap = array_replace($commandMap, $this->resolveCommandServices($builder, $service));
		}

		/** @var ServiceDefinition $commandLoaderDef */
		$commandLoaderDef = $builder->getDefinition($this->prefix('commandLoader'));
		$commandLoaderDef->setArguments(['@container', $commandMap]);

		// Register event dispatcher, if available
		try {
			$dispatcherDef = $builder->getDefinitionByType(EventDispatcherInterface::class);
			$applicationDef->addSetup('setDispatcher', [$dispatcherDef]);
		} catch (MissingServiceException) {
			// Event dispatcher is not installed, ignore
		}
	}

	/**
	 * Returns the "name => service" map for a console command (including aliases), or an empty array otherwise.
	 * Commands are detected via Command inheritance, the "console.command" tag, or the #[AsCommand] attribute.
	 *
	 * @return array<string, string>
	 */
	private function resolveCommandServices(ContainerBuilder $builder, Definition $service): array
	{
		$serviceName = $service->getName();
		$type = $service->getType();

		if ($serviceName === null || $type === null) {
			return [];
		}

		$tag = $service->getTag('console.command');
		$reflectionClass = new ReflectionClass($type);
		$extendsCommand = $reflectionClass->isSubclassOf(Command::class);
		$asCommandAttribute = ($reflectionClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

		if (!$extendsCommand) {
			if ($tag === null && $asCommandAttribute === null) {
				return [];
			}

			if (!$reflectionClass->hasMethod('__invoke')) {
				throw new ServiceCreationException(sprintf(
					'Service "%s" of type "%s" is registered as a console command (via "console.command" tag or #[AsCommand] attribute), but is neither a subclass of "%s" nor has an __invoke()" method.',
					$serviceName,
					$type,
					Command::class,
				));
			}
		}

		// The resolved name may be pipe-separated (name|alias1|alias2).
		// A leading pipe marks a hidden command, as in Symfony's Command constructor.
		$aliases = explode('|', $this->resolveCommandName($type, $tag, $asCommandAttribute));
		/** @var string $commandName */
		$commandName = array_shift($aliases);
		$isHidden = $commandName === '';

		if ($isHidden) {
			/** @var string $commandName */
			$commandName = array_shift($aliases);
		}

		$commandServiceName = $serviceName;

		if (!$extendsCommand) {
			$commandServiceName = $this->registerInvokableCommand(
				$builder,
				$serviceName,
				$commandName,
				$aliases,
				$isHidden,
				$asCommandAttribute,
			);
		}

		return array_fill_keys([$commandName, ...$aliases], $commandServiceName);
	}

	/**
	 * @param class-string $type
	 */
	private function resolveCommandName(string $type, mixed $tag, ?AsCommand $asCommandAttribute): string
	{
		$commandName = null;

		if (is_string($tag)) {
			$commandName = $tag;
		} elseif (is_array($tag)) {
			$commandName = $tag['name'] ?? null;
		}

		if (is_string($commandName) && $commandName !== '') {
			return $commandName;
		}

		if ($asCommandAttribute !== null) {
			$commandName = $asCommandAttribute->name;
		} elseif (is_callable([$type, 'getDefaultName'])) {
			$commandName = $type::getDefaultName();
		}

		if (!is_string($commandName) || $commandName === '') {
			throw new ServiceCreationException(sprintf('Command "%s" missing #[AsCommand] attribute', $type));
		}

		return $commandName;
	}

	/**
	 * Registers a new Command wrapper service for an invokable command.
	 *
	 * @param string[] $aliases
	 */
	private function registerInvokableCommand(
		ContainerBuilder $builder,
		string $invokableServiceName,
		string $commandName,
		array $aliases,
		bool $isHidden,
		?AsCommand $asCommandAttribute,
	): string
	{
		$commandServiceName = $this->prefix($invokableServiceName . '.command');
		$commandDef = $builder->addDefinition($commandServiceName)
			->setFactory(Command::class)
			->setAutowired(false)
			->addSetup('setCode', ['@' . $invokableServiceName])
			->addSetup('setName', [$commandName]);

		if ($aliases !== []) {
			$commandDef->addSetup('setAliases', [$aliases]);
		}

		if ($isHidden) {
			$commandDef->addSetup('setHidden', [true]);
		}

		if ($asCommandAttribute === null) {
			return $commandServiceName;
		}

		if (($asCommandAttribute->description ?? '') !== '') {
			$commandDef->addSetup('setDescription', [$asCommandAttribute->description]);
		}

		if (($asCommandAttribute->help ?? '') !== '') {
			$commandDef->addSetup('setHelp', [$asCommandAttribute->help]);
		}

		foreach ($asCommandAttribute->usages as $usage) {
			$commandDef->addSetup('addUsage', [$usage]);
		}

		return $commandServiceName;
	}

}
