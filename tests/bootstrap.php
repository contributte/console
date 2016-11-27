<?php

use Tester\Environment;
use Tester\Helpers;

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

// Configure environment
Environment::setup();
date_default_timezone_set('Europe/Prague');

// Create temporary directory
define('TMP_DIR', __DIR__ . '/tmp');
@mkdir(TMP_DIR, 0777, TRUE);
define('CACHE_DIR', TMP_DIR . '/cache');
@mkdir(CACHE_DIR, 0777, TRUE);
define('TEMP_DIR', TMP_DIR . '/cases/' . getmypid());
@mkdir(TEMP_DIR, 0777, TRUE);

// Purge temporary directory
Helpers::purge(TEMP_DIR);

/**
 * @param Closure $function
 * @return void
 */
function test(\Closure $function)
{
	$function();
}
