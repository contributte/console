<?php

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\Helper\ContainerHelper;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Symfony\Component\Console\Command\Command;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class ConsoleExtension extends CompilerExtension
{

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
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli') return;

		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

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
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli') return;

		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);
		$application = $builder->getDefinition($this->prefix('application'));

		// Setup URL in CLI
		if ($builder->hasDefinition('http.request') && $config['url'] !== NULL) {
			$builder->getDefinition('http.request')
				->setClass(Request::class, [new Statement(UrlScript::class, [$config['url']])]);
		}

		// Register all commands
		$commands = $builder->findByType(Command::class);
		foreach ($commands as $name => $command) {
			$application->addSetup('add', [$command]);
		}
	}

}
