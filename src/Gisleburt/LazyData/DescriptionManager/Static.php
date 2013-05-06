<?php

	namespace Gisleburt\LazyData;

	/**
	 * Stores table descriptions in static methods. Since this method must
	 * retrieve the descriptions once per execution it is not recommended
	 * unless no other storage method exists.
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */

	class DescriptionManager_Static implements DescriptionManager {
		
		protected $namespace;
		
		protected static $descriptions;
		
		/**
		 * Creating this object requires a namespace for storage
		 * @param string $namespace
		 * @throws Exception
		 */
		public function __construct($namespace) {
			
			if(!is_string($namespace))
				throw new Exception('$namespace must be a string');
			
			$this->namespace = $namespace;
			
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::initialise()
		 */
		public function initialise() {
			
			if(!isset(static::$descriptions))
				static::$descriptions = array();
			
			if(!isset(static::$descriptions[$this->namespace]))
				static::$descriptions[$this->namespace] = array();
			
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::isInitialised()
		 */
		public function isInitialised() {
			return isset(static::$descriptions) && isset(static::$descriptions[$this->namespace]);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::getTableDescription()
		 */
		public function getTableDescription() {
			return static::$descriptions[$this->namespace];
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::getFieldDescription()
		 */
		public function getFieldDescription($fieldName) {
			if(isset(static::$descriptions[$this->namespace][$fieldName]))
				return static::$descriptions[$this->namespace][$fieldName];
			return null;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::saveFieldDescription()
		 */
		public function saveFieldDescription($fieldName, FieldDescription $fieldDescription) {
			static::$descriptions[$this->namespace][$fieldName] = $fieldDescription;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see DescriptionManager::saveTableDescription()
		 */
		public function saveTableDescription(array $tableDescription) {
			foreach($tableDescription as $fieldName => $fieldDescription) 
				$this->saveFieldDescription($fieldName, $fieldDescription);
		}
		
		
	}