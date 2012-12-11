<?php

	namespace Gisleburt\LazyData;

	/**
	 * Links on LazyData object to another
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */
	
	class Relationship {
		
		/**
		 * The name of the class that will be loaded by this relationship
		 * @var string
		 */
		public $class;
		
		/**
		 * The field name used for loading the class
		 * @var string
		 */
		public $fieldTo;
		
		/**
		 * Database Object
		 * @var PDO
		 */
		protected $_pdo;
		
		public function __construct(array $config, PDO $pdo = null) {
			foreach($config as $field => $value)
				$this->$field = $value;
			$this->_pdo = $pdo;
		}
		
		/**
		 * Loads and returns the LazyData object
		 * @param mixed $value
		 * @return LazyData
		 */
		public function load($value) {
			$object = new $this->class();
			$object->loadBy($this->fieldTo, $value);
			return $object;
		}
		
		/**
		 * Loads an array of the LazyData object based on the given value
		 * @param mixed $value
		 * @return LazyData[]
		 */
		public function loadMany($value) {
			return call_user_func("$this->class::getDataBy", $this->fieldTo, $value);
		}

	}