<?php

	/**
	 * Extend to Schema level abstract, then extend to tables
	 * 
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @abstract
	 * @version 0.1
	 * @package LazyData
	 * @uses LazyData_Exception
	 */
	
	abstract class LazyData_Abstract {
		
		//
		// Override these
		//
		
		/**
		 * The location of the host for the database
		 * @var string eg. '127.0.0.1'
		 */
		protected $_host;
		
		/**
		 * The username to access the database.
		 * @var string
		 */
		protected $_username;
		
		/**
		 * The password for the database user
		 * @var string
		 */
		protected $_password;
		
		/**
		 * The Schema/Database name
		 * @var string
		 */
		protected $_schema;
		
		/**
		 * The table this LazyData object represents
		 * @var string
		 */
		protected $_table;
		
		/**
		 * Unless otherwise specificed, how should results be sorted
		 * @var string
		 */
		protected $_order;
		
		//
		// Internal mechanisms
		//
		
		/**
		 * Database connection
		 * @var PDO
		 */
		protected $_pdo;
		
		/**
		 * The name of the instantiated class
		 * @var string
		 */
		protected $__class__;
		
		/**
		 * Field data for all LazyData objects
		 * @var array
		 */
		static protected $_fields;
		
		/**
		 * The primary key for this table
		 * @var string
		 */
		protected $_primaryKey;
		
		/**
		 * Fields as a string for the database
		 * @var string
		 */
		protected $_fieldsString;
		
		/**
		 * Value placeholders as a string for the database
		 * @var string
		 */
		protected $_valuesString;
		
		/**
		 * String of fields with matching placeholder values
		 * @var string
		 */
		protected $_updateString;
		
		//
		// Flags
		//
		
		/**
		 * Record when the record was created
		 * @var boolean
		 */
		protected $_safeCreate;
		
		/**
		 * Record when the record was last modified
		 * @var boolean
		 */
		protected $_safeModify;
		
		/**
		 * Record the date the record was deleted (rather than actually deleting it)
		 * @var boolean
		 */
		protected $_safeDelete;
		
		/**
		 * List of fields to look out for and which flag to set to true if found
		 * @var array (Field => Flag)
		 */
		protected $_specialFields = array(
				'date_created'  => '_safeCreate',
				'date_modified' => '_safeModify',
				'date_deleted'  => '_safeDelete',	
			);
		
		//
		// Methods
		//
		
		/**
		 * Create an empty object, or loads one by id.
		 * @param int $id
		 */
		public function __construct($id = null) {
			
			$this->__class__ = get_called_class();
			
			if(!isset(self::$_fields))
				self::$_fields = array();
			
			// Get a connection
			$this->_getPDO();
			
			// Get field names
			$this->_setFields();
			
			// Set the helper strings
			$this->_setStrings();
			
			// If it's not already been set, set the default order to PK
			if(!$this->_order)
				$this->_order = "$this->_primaryKey DESC";
			
			// Now we can load any required data
			if($id)
				$this->load($id);
			
		}
		
		
		/**
		 * Tries to get a reference to a PDO object from the database manager
		 * @uses LazyData_Database
		 * @throws LazyData_Exception
		 */
		protected function _getPDO() {
			
			// Get a reference to a database connection, to be used later
			$this->_pdo = LazyData_Database::getMysqlConncetion(
					$this->_host,
					$this->_username,
					$this->_password,
					$this->_schema
				);
			
			// If we didn't get a connection something is wrong
			if(!$this->_pdo) 
				throw new LazyData_Exception(
						'PDO connection could not be established',
						LazyData_Exception::MESSAGE_DATABASE
					);
			
			// We don't need this data anymore, delete it so it never gets dumped to screens.
			$this->_host = $this->_username = $this->_password = '';
			
		}
		
		
		/**
		 * Takes any class variables that don't begin with an underscore and stores
		 * them for mapping to the database. Calls _mapFields();
		 * @throws LazyData_Exception
		 */
		protected function _setFields() {
			
			$className = $this->__class__;
			if(!isset(self::$_fields[$className])) {
				self::$_fields[$className] = array();
			
				$fields = array_keys(get_object_vars($this));
				foreach ($fields as $field) {
					if(substr($field, 0, 1) != '_')
						self::$_fields[$className][$field] = null;
				}
				$this->_mapFields();
			}
			if(empty(self::$_fields[$className]))
				throw new LazyData_Exception("No fields were found in this LiteTable: $className", LazyData_Exception::MESSAGE_BADCODE);
			
			$fields = $this->getFields();
			foreach($fields as $field => $type) {
				/* @var $type LazyData_Field */
				if('PRI' == $type->Key)
					$this->_primaryKey = $field;
				if(isset($this->_specialFields[$field]))
					$this->{$this->_specialFields[$field]} = true;
			}
			
		}
		
		
		/**
		 * Maps the class fields to database fields and maps their type 
		 * @throws LazyData_Exception
		 */
		protected function _mapFields() {
			
			$className = $this->__class__;
			
			try {
				$statement = $this->_pdo->prepare("DESCRIBE $this->_table");
				$statement->execute();
				
				// Use the describe to map a type to each field
				while($info = $statement->fetchObject('LazyData_Field')) {
					if(array_key_exists($info->Field, self::$_fields[$className]) // If the db field is known, save the data type
							|| isset($this->_specialFields[$info->Field])) {      // Special Fields are optional
						self::$_fields[$className][$info->Field] = $info;
					}
				}
				foreach(self::$_fields[$className] as $field => $info) {
					if(!$info)
						unset(self::$_fields[$className][$field]);
				}
				
			} catch (Exception $e) {
				throw new LazyData_Exception("PDO couldn't describe $this->_schema.$this->_table", LazyData_Exception::MESSAGE_DATABASE, $e);
			}
			
		}
		
		/**
		 * Clears the data from this object. Does not clear fields beginning with _
		 */
		public function clearValues() {
			$fields = array_keys(get_object_vars($this));
			foreach ($fields as $field)
				if(substr($field, 0, 1) != '_')
					$this->$field = null;
		}
		
		/**
		 * Loads a record based on id
		 * @param int $id
		 */
		public function load($id) {

			$id = (int)$id; // Quick cleanse			
			$this->loadBy($this->_primaryKey, $id);

		}
		
		/**
		 * Loads data based on the value of a field
		 * @param string $field
		 * @param string $value
		 */
		public function loadBy($field, $value) {
			$fields = $this->getFields();
			if(array_key_exists($field, $fields)) {
				$this->loadWhere("$field = $value");
			}
		}
		
		/**
		 * Loads a record with a given where
		 * @param string $where
		 * @param int $count Load this many
		 * @param int $offset Skip this many
		 * @param string $order Order strin
		 * @param bool forceLoad Ignore safe delete and load it if it exists
		 */
		public function loadWhere($where, $count = 1, $offset = 0, $order = null, $forceLoad = false) {
			$this->clearValues();
			if($this->_safeDelete && !$forceLoad)
				$where = "($where) AND date_deleted = 0";
			if(!$order)
				$order = $this->_order;
			$query = "SELECT $this->_fieldsString FROM $this->_table WHERE $where ORDER BY $order LIMIT $offset,$count";
			$statement = $this->_pdo->prepare($query);
			$statement->execute();
			if($row=$statement->fetch(PDO::FETCH_ASSOC))
				$this->setValues($row, false);
		}
		
		/**
		 * Inserts data into database
		 */
		public function insert() {
			$this->setDateCreated();
			$values = $this->getValues();
			$query = "INSERT INTO {$this->_table} ($this->_fieldsString) VALUES($this->_valuesString)";
			$statement = $this->_pdo->prepare($query);
			$fields = $this->getFields();
			foreach($fields as $field => $type) {
				$statement->bindParam($field, $this->$field);
			}
			$statement->execute();
			$this->{$this->_primaryKey} = $this->_pdo->lastInsertId();
		}
		
		/**
		 * Updates the current entry in the database
		 */
		public function update() {
			$this->setDateModified();
			$values = $this->getValues();
			$query = "UPDATE {$this->_table} SET $this->_updateString WHERE $this->_primaryKey = ".(int)$this->getPrimaryKey();
			$statement = $this->_pdo->prepare($query);
			$fields = $this->getFields();
			foreach($fields as $field => $type) {
				$statement->bindParam($field, $this->$field);
			}
			$statement->execute();
		}
		
		/**
		 * Will insert or update record depending on if its PK is set.
		 */
		public function save() {
			if($this->getPrimaryKey())
				$this->update();
			else
				$this->insert();
		}
		
		
		/**
		 * Sets field values from an associative array. Will not set fields starting with _
		 * @param array $values
		 */
		public function setValues(array $values, $safe = true) {
			$this->clearValues();
			foreach($values as $field=>$value) {
				if((substr($field, 0, 1) != '_')) {
					if($safe)
						$this->$field = $this->_pdo->quote($value);
					else
						$this->$field = $value;
				}
			}
		}
		
		/**
		 * Returns an associative array of the current values (but not ones begining with _)
		 * @param bool all Return all values, not just the ones relevant to the table
		 */
		public function getValues($all = null) {
			$values = array();
			$fields = $this->getFields();
			if($all) {
				foreach($fields as $field => $type) {
					$values[$field] = $this->$field;
				}
			}
			else {
				$allowedFields = $this->getFields();
				foreach($fields as $field => $type) {
					if(in_array($field, $allowedFields))
						$values[$field] = $this->$field;
				}
			}
		}

		/**
		 * Returns the fields in both the object and database
		 */
		public function getFields() {
			return self::$_fields[$this->__class__];
		}
		
		/**
		 * Returns the info for a given field
		 * @param LazyData_Field $fieldName
		 */
		public function getInfoForField($fieldName) {
			if(isset(self::$_fields[$this->__class__][$fieldName]))
				return self::$_fields[$this->__class__][$fieldName];
		}
		
		/**
		 * Checks through a list of fields and returns the acceptable ones
		 * @param array $fields
		 * @return array $fields
		 */
		public function checkFields(array $fields) {
			$returnFields = array();
			$allowedFields = $this->getFields();
			foreach($fields as $key => $field)
				if(!in_array($field, $allowedFields))
					unset($fields[$key]);
			return $fields;
		}
		
		/**
		 * Setup the strings for use in querys
		 */
		protected function _setStrings() {
			$this->_fieldsString = '';
			$this->_valuesString = '';
			$this->_updateString = '';
			$fields = $this->getFields();
			foreach($fields as $field => $type) {
				$this->_fieldsString .= "$field, ";
				$this->_valuesString .= ":$field, ";
				$this->_updateString .= "$field = :$field, "; 
			}
			$this->_fieldsString = rtrim($this->_fieldsString, ', ');
			$this->_valuesString = rtrim($this->_valuesString, ', ');
			$this->_updateString = rtrim($this->_updateString, ', ');
		}
		
		/**
		 * Get a reference to the PDO object for this table
		 * @return PDO
		 */
		public function getPDO() {
			return $this->_pdo;
		}
		
		/**
		 * Returns the tablename
		 * @return string
		 */
		public function getTable() {
			return $this->_table;
		}
		
		/**
		 * Returns the default order of the table
		 * @return string
		 */
		public function getOrder() {
			return $this->_order;
		}
		
		/**
		 * Gets an array of LazyData objects
		 * @param int $count
		 * @param int $offset
		 * @param string $order
		 * @return array
		 */
		public static function getData($count = 1, $offset = 0, $order = null) {
			return static::getDataWhere('1', $count, $offset, $order);
		}
		
		/**
		 * Get an array of LazyData objects with a WHERE statement
		 * @param string $where
		 * @param int $count
		 * @param int $offset
		 * @param string $order
		 * @return array
		 */
		public static function getDataWhere($where, $count = 1, $offset = 0, $order = null) {
			$datas = array();
			$manager = new static();
			$pdo = $manager->getPDO();
			if(!$order)
				$order = $manager->getOrder();
			$statement = $pdo->prepare("SELECT {$manager->getFieldsString()} FROM {$manager->getTable()} WHERE $where ORDER BY $order LIMIT $offset,$count");
			$statement->execute();
			while($data = $statement->fetchObject(get_called_class())) {
				if(!($data->_safeDelete && $row[$data->getSafeDeleteField()] > 0)) {
					$datas[] = $data;
				}
			}
			return $datas;
		}
		
		/**
		 * Deletes the current record from the database (or safe-deletes if applicable)
		 */
		public function delete() {
			if($this->getPrimaryKey()) {
				if($this->_safeDelete) {
					$this->setDateDeleted();
					$this->update();
				} else {
					$statement = $this->_pdo->prepare("DELETE FROM $this->_table WHERE $this->_primaryKey = :pk");
					$statement->bindParam(':pk', $this->{$this->_primaryKey});
					$statement->execute();
				}
			}
		}
		
		/**
		 * Reload the current record from the database
		 */
		public function reload() {
			$this->load($this->getPrimaryKey());
		}
		
		/**
		 * Set the safe create field
		 * @return bool
		 */
		public function setDateCreated() {
			if($this->_safeCreate) {
				$fieldName = array_search('_safeCreate', $this->_specialFields);
				$this->_setFieldToNow($fieldName);
			}
		}
		
		/**
		 * Set the safe create field
		 * @return bool
		 */
		public function setDateModified() {
			if($this->_safeModify) {
				$fieldName = array_search('_safeModify', $this->_specialFields);
				$this->_setFieldToNow($fieldName);
			}
		}
		
		/**
		 * Set the safe create field
		 * @return bool
		 */
		public function setDateDeleted() {
			if($this->_safeDelete) {
				$fieldName = array_search('_safeDelete', $this->_specialFields);
				$this->_setFieldToNow($fieldName);
			}
		}
		
		/**
		 * Sets the value of the given field to the current time, date or datetime depending on its type
		 * @param string $fieldName
		 */
		protected function _setFieldToNow($fieldName) {
			if($info = $this->getInfoForField($fieldName)) {
				switch($info->Type) {
			
					case 'YEAR':
						if($info->Size == 2)
							$this->$fieldName = date('y');
						else
							$this->$fieldName = date('Y');
						break;
					
					case 'DATE':
						$this->$fieldName = date('Y-m-d');
						break;
					
					case 'TIME':
						$this->$fieldName = date('h:i:s');
						break;
			
					case 'DATETIME':
					case 'TIMESTAMP':
					default:
						$this->$fieldName = date('Y-m-d h:i:s');
						break;
			
				}
			}
		}

		/**
		 * Returns the value of the primary key
		 */
		public function getPrimaryKey() {
			return $this->{$this->_primaryKey};
		}
		
		public function getSafeCreateField() {
			return array_search('_safeCreate', $this->_specialFields);
		}
		
		public function getSafeModifyField() {
			return array_search('_safeModify', $this->_specialFields);
		}
		
		public function getSafeDeleteField() {
			return array_search('_safeDelete', $this->_specialFields);
		}
		
		public function getFieldsString() {
			return $this->_fieldsString;
		}
		
	}