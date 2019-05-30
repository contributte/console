<?php declare(strict_types = 1);

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Exception\Logical\InvalidArgumentException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

/**
 * @property-read stdClass $config
 */
class ConsoleExtension extends CompilerExtension
{

	public const COMMAND_TAG = 'console.command';

	/** @var bool */
	private $cliMode;

	public function __construct(bool $cliMode = false)
	{
		if (func_num_args() <= 0) {
			throw new InvalidArgumentException(sprintf('Provide CLI mode, e.q. %s(%%consoleMode%%).', self::class));
		}

		$this->cliMode = $cliMode;
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'url' => Expect::string(),
			'name' => Expect::string(),
			'version' => Expect::string(),
			'catchExceptions' => Expect::bool(),
			'autoExit' => Expect::bool(),
			'helperSet' => Expect::string(),
			'helpers' => Expect::listOf('string'),
			'lazy' => Expect::bool(true),
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
		$config = (array) $this->config;

		$application = $builder->addDefinition($this->prefix('application'))
			->setFactory(Application::class);

		if ($config['name'] !== null) {
			$application->addSetup('setName', [$config['name']]);
		}

		if ($config['version'] !== null) {
			$application->addSetup('setVersion', [$config['version']]);
		}

		if ($config['catchExceptions'] !== null) {
			$application->addSetup('setCatchExceptions', [(bool) $config['catchExceptions']]);
		}

		if ($config['autoExit'] !== null) {
			$application->addSetup('setAutoExit', [(bool) $config['autoExit']]);
		}

		if ($config['helperSet'] !== null) {
			if (is_string($config['helperSet']) && Strings::startsWith($config['helperSet'], '@')) {
				// Add already defined service
				$application->addSetup('setHelperSet', [$config['helperSet']]);
			} elseif (is_string($config['helperSet'])) {
				// Parse service definition
				$helperSetDef = $builder->addDefinition($this->prefix('helperSet'))
					->setFactory($config['helperSet']);
				$application->addSetup('setHelperSet', [$helperSetDef]);
			} else {
				throw new ServiceCreationException(sprintf('Unsupported definition of helperSet'));
			}
		}

		if ($config['helpers']) {
			foreach ($config['helpers'] as $n => $helper) {
				if (is_string($helper) && Strings::startsWith($helper, '@')) {
					// Add already defined service
					$application->addSetup(new Statement('$service->getHelperSet()->set(?)', [$helper]));
				} elseif (is_string($helper)) {
					// Parse service definition
					$helperDef = $builder->addDefinition($this->prefix('helperSet'))
						->setFactory($helper);
					$application->addSetup(new Statement('$service->getHelperSet()->set(?)', [$helperDef]));
				} else {
					throw new ServiceCreationException(sprintf('Unsupported definition of helper'));
				}
			}
		}

		if ($config['lazy'] === true) {
			$builder->addDefinition($this->prefix('commandLoader'))
				->setType(CommandLoaderInterface::class)
				->setFactory(ContainerCommandLoader::class);

			$application->addSetup('setCommandLoader', ['@' . $this->prefix('commandLoader')]);
		}
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
		$config = (array) $this->config;

		/** @var ServiceDefinition $applicationDef */
		$applicationDef = $builder->getDefinition($this->prefix('application'));

		// Setup URL for CLI
		if ($builder->hasDefinition('http.request') && $config['url'] !== null) {
			/** @var ServiceDefinition $httpDef */
			$httpDef = $builder->getDefinition('http.request');
			$httpDef->setFactory(Request::class, [new Statement(UrlScript::class, [$config['url']])]);
		}

		// Register all commands (if they are not lazy-loaded)
		// otherwise build a command map for command loader
		$commands = $builder->findByType(Command::class);

		if ($config['lazy'] === false) {
			// Iterate over all commands and add to console
			foreach ($commands as $serviceName => $service) {
				$applicationDef->addSetup('add', [$service]);
			}
		} else {
			$commandMap = [];

			// Iterate over all commands and build commandMap
			foreach ($commands as $serviceName => $service) {
				$tags = $service->getTags();
				$entry = ['name' => null, 'alias' => null];

				if (isset($tags[self::COMMAND_TAG])) {
					// Parse tag's name attribute
					if (is_string($tags[self::COMMAND_TAG])) {
						$entry['name'] = $tags[self::COMMAND_TAG];
					} elseif (is_array($tags[self::COMMAND_TAG])) {
						$entry['name'] = Arrays::get($tags[self::COMMAND_TAG], 'name', null);
					}
				} else {
					// Parse it from static property
					$entry['name'] = call_user_func([$service->getType(), 'getDefaultName']);
				}

				// Validate command name
				if (!isset($entry['name'])) {
					throw new ServiceCreationException(
						sprintf(
							'Command "%s" missing tag "%s[name]" or variable "$defaultName".',
							$service->getType(),
							self::COMMAND_TAG
						)
					);
				}

				// Append service to command map
				$commandMap[$entry['name']] = $serviceName;
			}

			/** @var ServiceDefinition $commandLoaderDef */
			$commandLoaderDef = $builder->getDefinition($this->prefix('commandLoader'));
			$commandLoaderDef->getFactory()->arguments = ['@container', $commandMap];
		}
	}

}
