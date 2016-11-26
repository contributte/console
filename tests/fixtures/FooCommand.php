<?php

namespace Tests\Fixtures;

use Contributte\Console\Command\BaseCommand;

final class FooCommand extends BaseCommand
{

	/**
	 * Configure command
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('foo');
	}

}
