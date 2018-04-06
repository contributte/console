<?php

namespace Contributte\Console\Command;

use Symfony\Component\Console\Command\Command;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
abstract class AbstractCommand extends Command
{

	/**
	 * @param string|null $name
	 */
	public function __construct($name = NULL)
	{
		parent::__construct($name);
		trigger_error(sprintf('Extending %s is deprecated, extend %s directly.', self::class, Command::class), E_USER_DEPRECATED);
	}

}
