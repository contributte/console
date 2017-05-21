<?php

namespace Tests\Fixtures;

use Symfony\Component\Console\Helper\Helper;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class FooHelper extends Helper
{

	/**
	 * @return string The canonical name
	 */
	public function getName()
	{
		return 'foo';
	}

}
