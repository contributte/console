<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\ThrowingCommand;

require_once __DIR__ . '/../../../bootstrap.php';

test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		services:
			- Tests\Fixtures\ThrowingCommand
			- Symfony\Component\EventDispatcher\EventDispatcher
		', 'neon'));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class();

	/** @var Application $application */
	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $container->getByType(Application::class));

	/** @var EventDispatcherInterface $dispatcher */
	$dispatcher = $container->getByType(EventDispatcherInterface::class);
	$dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event): void {
		Assert::same(ThrowingCommand::ERROR_MESSAGE, $event->getError()->getMessage());
	});

	Assert::exception(function () use ($application): void {
		$application->doRun(
			new ArrayInput([
				'command' => 'throwing',
			]),
			new NullOutput()
		);
	}, Throwable::class, ThrowingCommand::ERROR_MESSAGE);
});
