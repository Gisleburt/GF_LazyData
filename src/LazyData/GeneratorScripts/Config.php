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
		 * Vendor (your) name, used for file save and namespace
		 * @var string
		 */
		public $vendor;

		/**
		 * Package name, used for file save and namespace
		 * @var string
		 */
		public $package;

		/**
		 * Any subdirectories, used for file save and namespace
		 * @var string (single string in the form one/two)
		 */
		public $subDirectory;

		/**
		 * Created automatically if not set
		 * @var string
		 */
		public $saveTo;

		/**
		 * Created automatically if not set
		 * @var string
		 */
		public $namespace;

		/**
		 * This is the top level of your library folder
		 * The files will be generated bellow here either in
		 * vendor/package/subDirectory or wherever saveTo is set
		 * @var string
		 */
		public $libraryLocation;


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
			if(!$this->vendor)
				throw new Exception('Vendor not set.');
			if(!$this->package)
				throw new Exception('Package not set.');

			if(!$this->saveTo) {
				$this->saveTo = "$this->vendor/$this->package";
				if($this->subDirectory)
					$this->saveTo .= "/$this->subDirectory";
			}

			if(!$this->namespace)
				$this->namespace = str_replace('/', '\\', $this->saveTo);
			$this->saveTo = "$this->libraryLocation/$this->saveTo";

			return true;
		}
		
	}
