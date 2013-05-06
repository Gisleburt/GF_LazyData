<?php

	//
	// This bit loads the autoloader. You may wish to use a different PSR-0
	// autoloader in which case just replace this section.
	//

	$libraryDir = __DIR__.'/../../..';
	require_once $libraryDir.'/Gisleburt/Tools/Autoloader.php';
	\Gisleburt\Tools\Autoloader::$incDirs[] = $libraryDir;
	spl_autoload_register('\Gisleburt\Tools\Autoloader::psr0');