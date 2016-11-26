<?php

/**
 * Test: DI\ConsoleExtension
 */

use Contributte\Console\Command\BaseCommand;
use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\Assert;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../bootstrap.php';

test(function () {
    $loader = new ContainerLoader(TEMP_DIR, TRUE);
    $class = $loader->load(function (Compiler $compiler) {
        $compiler->addExtension('console', new ConsoleExtension());
    }, [microtime(), 1]);

    /** @var Container $container */
    $container = new $class;

    Assert::count(0, $container->findByType(BaseCommand::class));
});


test(function () {
    $loader = new ContainerLoader(TEMP_DIR, TRUE);
    $class = $loader->load(function (Compiler $compiler) {
        $compiler->addExtension('console', new ConsoleExtension());
        $compiler->loadConfig(\Tester\FileMock::create('
        services:
            - Tests\Fixtures\FooCommand
        ', 'neon'));
    }, [microtime(), 2]);

    /** @var Container $container */
    $container = new $class;

    Assert::count(1, $container->findByType(BaseCommand::class));
    Assert::type(FooCommand::class, $container->getByType(BaseCommand::class));
});
