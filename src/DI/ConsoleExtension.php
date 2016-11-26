<?php

namespace Contributte\Console\DI;

use Contributte\Console\Application;
use Contributte\Console\Command\BaseCommand;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\Http\Request;
use Nette\Http\UrlScript;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class ConsoleExtension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'url' => NULL,
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

		$builder->addDefinition($this->prefix('application'))
			->setClass(Application::class);
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
		$commands = $builder->findByType(BaseCommand::class);
		foreach ($commands as $name => $command) {
			$application->addSetup('add', [$command]);
		}
	}

}
