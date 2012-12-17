<?php 

	use Gisleburt\LazyData\GeneratorScripts\Generator;
	use Gisleburt\LazyData\GeneratorScripts\Config;

	require_once 'Initialise.php';

	//
	// This bit you'll want to change to your details before running
	//
	
	$config = new Config();

	$config->dbHost = 'localhost';
	$config->dbUsername = 'dummyuser';
	$config->dbPassword = 'dummypassword';
	$config->dbSchema = 'thymely';

	$config->libraryLocation = $libraryDir;
	$config->vendor = 'Gisleburt';
	$config->package = 'Thymely';
	$config->subDirectory = 'LazyData';

	//
	// Finally this generates it
	//
	
	$generator = new Generator($config);
	$generator->createAbstract();
	$generator->createClasses();
