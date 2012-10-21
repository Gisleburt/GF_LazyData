<?php

	/**
	 * Links on LazyData object to another
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */

	class LazyData_Relationship {
		
		/**
		 * The name of the class that will be loaded by this relationship
		 * @var string
		 */
		public $class;
		
		/**
		 * The field name used for loading the class
		 * @var string
		 */
		public $fieldFrom;
		
		/**
		 * The field name used for loading the class
		 * @var string
		 */
		public $fieldTo;
		
		public function __construct(array $config) {
			foreach($config as $field => $value)
				$this->$field = $value;
		}
		
		/**
		 * Loads and returns the LazyData object
		 * @param mixed $value
		 * @return LazyData_Abstract
		 */
		public function load($value) {
			$object = new $this->class();
			$object->loadBy($this->fieldTo, $value);
			return $object;
		}
		
		public function loadMany($value) {
			return call_user_func("$this->class::getDataBy", $this->fieldTo, $value);
		}
		
	}