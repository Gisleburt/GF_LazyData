<?php

	/**
	 * A simple way to grab a database handler without caring if it exists.
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */
	class LazyData_Database {
		
		/**
		 * An array of PDO connections to mysql databases
		 * @var PDO[]
		 */
		protected static $mysqlConnections = array();
		
		/**
		 * Options for use in PDO
		 * @var array
		 */
		public static $options;
		
		public static function getMysqlConncetion($database, $username, $password, $schema = null) {
			
			// Create an key for use in the $connections array
			$dbKey = $database;
			if($schema)
				$dbKey.=".$schema";
			
			// If the key doesn't exist, or doesn't work, create it
			if(!isset(self::$mysqlConnections[$dbKey][$username]) || self::$mysqlConnections[$dbKey][$username]) {
				$dsn = "mysql:host=$database";
				if($schema)
					$dsn.=";dbname=$schema";
				self::$mysqlConnections[$database][$username] = new PDO($dsn, $username, $password, self::$options);
			}
			return self::$mysqlConnections[$database][$username];
			
		}

		
		/**
		 * This class can not be instantiated
		 */
		private function __construct() {}
		
	}