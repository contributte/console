<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tester\Assert;
use Tests\Fixtures\ThrowingCommand;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					- Tests\Fixtures\ThrowingCommand
					- Symfony\Component\EventDispatcher\EventDispatcher
			NEON));
		})->build();

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
