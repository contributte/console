<?php

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Exception\Logical\InvalidArgumentException;
use Contributte\Console\Helper\ContainerHelper;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceCreationException;
use Nette\DI\Statement;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class ConsoleExtension extends CompilerExtension
{

	const COMMAND_TAG = 'console.command';

	/** @var array */
	private $defaults = [
		'url' => NULL,
		'name' => NULL,
		'version' => NULL,
		'catchExceptions' => NULL,
		'autoExit' => NULL,
		'helperSet' => NULL,
		'helpers' => [
			ContainerHelper::class,
		],
		'lazy' => FALSE,
	];

	/** @var bool */
	private $cliMode;

	/**
	 * @param bool $cliMode
	 */
	public function __construct($cliMode = FALSE)
	{
		if (func_num_args() <= 0) {
			throw new InvalidArgumentException(sprintf('Provide CLI mode, e.q. %s(%%consoleMode%%).', self::class));
		}

		$this->cliMode = $cliMode;
	}

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		// Skip if isn't CLI
		if ($this->cliMode !== TRUE) return;

		Validators::assertField($config, 'helpers', 'array|null');

		$application = $builder->addDefinition($this->prefix('application'))
			->setClass(Application::class);

		if ($config['name'] !== NULL) {
			$application->addSetup('setName', [$config['name']]);
		}

		if ($config['version'] !== NULL) {
			$application->addSetup('setVersion', [$config['version']]);
		}

		if ($config['catchExceptions'] !== NULL) {
			$application->addSetup('setCatchExceptions', [(bool) $config['catchExceptions']]);
		}

		if ($config['autoExit'] !== NULL) {
			$application->addSetup('setAutoExit', [(bool) $config['autoExit']]);
		}

		if ($config['helperSet'] !== NULL) {
			if (is_string($config['helperSet']) && Strings::startsWith($config['helperSet'], '@')) {
				// Add already defined service
				$application->addSetup('setHelperSet', [$config['helperSet']]);
			} else {
				// Parse service definition
				$helperSetDef = $builder->addDefinition($this->prefix('helperSet'));
				Compiler::loadDefinition($helperSetDef, $config['helperSet']);
				$application->addSetup('setHelperSet', [$helperSetDef]);
			}
		}

		if (is_array($config['helpers'])) {
			$helpers = 1;
			foreach ($config['helpers'] as $helper) {
				$helperDef = $builder->addDefinition($this->prefix('helper.' . $helpers++));
				Compiler::loadDefinition($helperDef, $helper);
				$application->addSetup(new Statement('$service->getHelperSet()->set(?)', [$helperDef]));
			}
		}

		if ($config['lazy'] === TRUE) {
			$builder->addDefinition($this->prefix('commandLoader'))
				->setClass(CommandLoaderInterface::class)
				->setFactory(ContainerCommandLoader::class);

			$application->addSetup('setCommandLoader', ['@' . $this->prefix('commandLoader')]);
		}
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		// Skip if isn't CLI
		if ($this->cliMode !== TRUE) return;

		$application = $builder->getDefinition($this->prefix('application'));

		// Setup URL for CLI
		if ($builder->hasDefinition('http.request') && $config['url'] !== NULL) {
			$builder->getDefinition('http.request')
				->setClass(Request::class, [new Statement(UrlScript::class, [$config['url']])]);
		}

		// Register all commands (if they are not lazy-loaded)
		// otherwise build a command map for command loader
		$commands = $builder->findByType(Command::class);

		if ($config['lazy'] === FALSE) {
			// Iterate over all commands and add to console
			foreach ($commands as $serviceName => $service) {
				$application->addSetup('add', [$service]);
			}
		} else {
			$commandMap = [];

			// Iterate over all commands and build commandMap
			foreach ($commands as $serviceName => $service) {
				$tags = $service->getTags();
				$entry = ['name' => NULL, 'alias' => NULL];

				if (isset($tags[self::COMMAND_TAG])) {
					// Parse tag's name attribute
					if (is_string($tags[self::COMMAND_TAG])) {
						$entry['name'] = $tags[self::COMMAND_TAG];
					} else if (is_array($tags[self::COMMAND_TAG])) {
						$entry['name'] = Arrays::get($tags[self::COMMAND_TAG], 'name', NULL);
					}
				} else {
					// Parse it from static property
					$entry['name'] = call_user_func([$service->getEntity(), 'getDefaultName']);
				}

				// Validate command name
				if (!isset($entry['name'])) {
					throw new ServiceCreationException(
						sprintf(
							'Command "%s" missing tag "%s[name]" or variable "$defaultName".',
							$service->getEntity(),
							self::COMMAND_TAG
						)
					);
				}

				// Append service to command map
				$commandMap[$entry['name']] = $serviceName;
			}

			$builder->getDefinition($this->prefix('commandLoader'))
				->getFactory()->arguments = ['@container', $commandMap];
		}
	}

}
