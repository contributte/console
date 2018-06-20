<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Helper\Helper;

class FooHelper extends Helper
{

	public function getName(): string
	{
		return 'foo';
	}

}
