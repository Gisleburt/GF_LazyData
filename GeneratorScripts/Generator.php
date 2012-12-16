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
					$this->config->dbServer,
					$this->config->dbUsername,
					$this->config->dbPassword,
					$this->config->dbSchema
				);
		}
		
		public function getTables() {
			$bob = $this->_pdo->prepare('SHOW TABLES');
			var_dump($bob);
		}
	}