<?php

	namespace Gisleburt\LazyData;

	/**
	 * An interface to be used to manage the storage of table descriptions
	 * used by the Lazy Data package
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */
	
	interface DescriptionManager {
		
		/**
		 * Creating this object requires a namespace for storage
		 * @param string $namespace
		 */
		public function __construct($namespace);
		
		/**
		 * Create storage area for descriptions
		 */
		public function initialise();
		
		/**
		 * Check if storage area is initialised
		 */
		public function isInitialised();
		
		/**
		 * Get an array of all field descriptions
		 */
		public function getTableDescription();
		
		/**
		 * Get a description of a particular field
		 * @param string $fieldName
		 */
		public function getFieldDescription($fieldName);
		
		/**
		 * Save a description of a particular field
		 * @param string $fieldName
		 * @param FieldDescription $fieldDescription
		 */
		public function saveFieldDescription($fieldName, FieldDescription $fieldDescription);
		
		/**
		 * Save an array of descriptions
		 * @param array $tableDescription
		 */
		public function saveTableDescription(array $tableDescription);
		
	}