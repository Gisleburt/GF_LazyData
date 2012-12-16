<?php 

	//
	// This bit loads the autoloader. You may wish to use a different PSR-0 
	// autoloader in wich case just replace this section.
	//

	$libraryDir = __DIR__.'/../../..';
	require_once $globalConfig->dir['library'].'/Gisleburt/Tools/Autoloader.php';
	\Gisleburt\Tools\Autoloader::$incDirs[] = $globalConfig->dir['library'];
	spl_autoload_register('\Gisleburt\Tools\Autoloader::psr0');
	
	echo 'ok';