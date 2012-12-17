<?php

	namespace Gisleburt\LazyData\GeneratorScripts;
	
	use Gisleburt\LazyData\Database;

	class Generator {
		
		/**
		 * The configuration for the script.
		 * @var Config
		 */
		public $config;
		
		/**
		 * PDO connection to MySQL database
		 * @var \PDO
		 */
		protected $_pdo;
		
		public function __construct(Config $config) {
			$this->config = $config;
			$this->config->checkConfig();
		}
		
		/**
		 * Get a connection to the MySQL database
		 */
		public function getDatabase() {
			$this->_pdo = Database::getMysqlConncetion(
					$this->config->dbHost,
					$this->config->dbUsername,
					$this->config->dbPassword,
					$this->config->dbSchema
				);
		}

		/**
		 * Get table data
		 */
		public function getTables() {
			if(!$this->_pdo)
				$this->getDatabase();
			$bob = $this->_pdo->prepare('SHOW TABLES');
			var_dump($bob->execute());
		}
	}
