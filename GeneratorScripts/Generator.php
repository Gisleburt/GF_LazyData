<?php

	namespace Gisleburt\LazyData\GeneratorScripts;
	
	use Gisleburt\LazyData\Database;
	use Gisleburt\LazyData\Exception;

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

		/**
		 * Tables in the database.
		 * @var string[]
		 */
		protected $_tables;

		/**
		 * Field information
		 * @var stdClass[]
		 */
		protected $_fields;
		
		public function __construct(Config $config) {
			$this->config = $config;
			$this->config->checkConfig();
			$this->tables = array();
			$this->_fields = array();
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

			$query = $this->_pdo->prepare('SHOW TABLES');
			if(!$query->execute())
				throw new Exception("Could not query schema {$this->config->dbSchema} on {$this->config->dbHost}");
			if(!$results = $query->fetchAll())
				throw new Exception("Could not get tables from {$this->config->dbSchema} on {$this->config->dbHost}");

			foreach($results as $result) {
				$this->_tables[] = $result[0];
			}
		}

		public function getTableFields() {

			if(!$this->tables)
				$this->getTables();

			$describes = array();
			foreach($this->_tables as $table) {
				$query = $this->_pdo->prepare("DESCRIBE $table;");
				$query->execute();
				$describes[$table] = $query->fetchAll(\PDO::FETCH_CLASS);
			}

			foreach($describes as $table => $describe) {
				foreach($describe as $field) {
					$name = ucwords(str_replace('_', ' ', $field->Field));
					$this->_fields[$table][$name] = new \stdClass();
					$this->_fields[$table][$name]->name  = $name;
					$this->_fields[$table][$name]->type  = Database::mySqlTypeToPhpType($field->Type);
					$this->_fields[$table][$name]->field = $field->Field;
				}
			}

		}

		public function createAbstract() {

			if(!$this->_fields)
				$this->getTableFields();

			$config = $this->config;
			$classname = str_replace(' ', '', ucwords(str_replace('_', ' ', $config->dbSchema))).'Abstract';
			ob_start();
			chdir(dirname($_SERVER['SCRIPT_FILENAME']));
			require 'Templates/AbstractCreator.php';
			$filedata = ob_get_contents();
			ob_end_clean();
			if(!is_dir($config->saveTo))
				mkdir($config->saveTo, 0775, true);
			file_put_contents("{$config->saveTo}/$classname.php", $filedata);

		}

		public function createClasses() {

			if(!$this->_fields)
				$this->getTableFields();

			$config = $this->config;

			foreach($this->_fields as $table => $fields) {
				$classname = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
				ob_start();
				chdir(dirname($_SERVER['SCRIPT_FILENAME']));
				require 'Templates/ClassCreator.php';
				$filedata = ob_get_contents();
				ob_end_clean();
				if(!is_dir($config->saveTo))
					mkdir($config->saveTo, 0775, true);
				file_put_contents("{$config->saveTo}/$classname.php", $filedata);

			}
		}

	}
