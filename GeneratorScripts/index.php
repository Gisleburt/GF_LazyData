<?php 

	use Gisleburt\LazyData\GeneratorScripts\Generator;
	use Gisleburt\LazyData\GeneratorScripts\Config;

	//
	// This bit loads the autoloader. You may wish to use a different PSR-0 
	// autoloader in wich case just replace this section.
	//

	$libraryDir = __DIR__.'/../../..';
	require_once $libraryDir.'/Gisleburt/Tools/Autoloader.php';
	\Gisleburt\Tools\Autoloader::$incDirs[] = $libraryDir;
	spl_autoload_register('\Gisleburt\Tools\Autoloader::psr0');
	
	//
	// This bit you'll want to change to your details before running
	//
	
	$config = new Gisleburt\LazyData\GeneratorScripts\Config();
	$config->dbHost = 'localhost';
	$config->dbUsername = 'dummyuser';
	$config->dbPassword = 'dummypassword';
	$config->dbSchema = 'thymely';
	$config->saveLocation = '/home/daniel/test/';
	
	$generator = new Generator($config);
	$generator->getTableFields();
