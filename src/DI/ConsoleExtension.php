<?php

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\Http\Request;
use Nette\Http\UrlScript;
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
			$application->addSetup('autoExit', [(bool) $config['autoExit']]);
		}

		if ($config['helperSet'] !== NULL) {
			$application->addSetup('setHelperSet', [$config['helperSet']]);
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
