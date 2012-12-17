<?php

	namespace Gisleburt\LazyData\GeneratorScripts;

	use Gisleburt\LazyData\Exception;

	class Config {
		
		//
		// Database info
		//
		
		/**
		 * Location of the database
		 * eg. 127.0.0.1
		 * @var string
		 */
		public $dbHost;
		
		/**
		 * Username to log into database
		 * @var string
		 */
		public $dbUsername;
		
		/**
		 * Password to log into the database
		 * @var string
		 */
		public $dbPassword;
		
		/**
		 * Schema to load
		 * @var string
		 */
		public $dbSchema;
		
		//
		// Other info
		//
		
		/**
		 * Where to save the files.
		 * This is also used for the namespace so use backslash seperators 
		 * @var string
		 */
		public $saveLocation;
		
		/**
		 * Just makes sure the config looks ok.
		 * @throws Exception
		 * @return boolean
		 */
		public function checkConfig() {
			if(!$this->dbHost)
				throw new Exception('Database host not set.');
			if(!$this->dbUsername)
				throw new Exception('Database username not set.');
			if(!$this->dbPassword)
				throw new Exception('Database password not set.');
			if(!$this->dbSchema)
				throw new Exception('Database schema not set.');
			if(!$this->saveLocation)
				throw new Exception('Save location not set.');
			return true;
		}
		
	}