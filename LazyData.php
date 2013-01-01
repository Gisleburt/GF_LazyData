<?php
	
	namespace Gisleburt\LazyData;

	/**
	 * Extend to Schema level abstract, then extend to tables
	 * 
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @abstract
	 * @version 0.7
	 * @package LazyData
	 */
	
	abstract class LazyData {
		
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
		
		/**
		 * An array of relationships to other LazyData classes
		 * @var unknown_type
		 */
		protected $_relationships = array();
		
		/**
		 * The name of the description manager class. Must implement DescriptionManager
		 * @var string
		 */
		protected $_descriptionManagerClass;
		
		//
		// Internal mechanisms
		//
		
		/**
		 * Database connection
		 * @var PDO
		 */
		protected $_pdo;
		
		/**
		 * The description manager oject
		 * @var DescriptionManager
		 */
		protected $_descriptionManager;
		
		/**
		 * The name of the instantiated class
		 * @var string
		 */
		protected $__class__;
		
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
			
			// Get a connection (do this first as until its done username and password are visible)
			$this->_getPDO();
			
			$this->__class__ = get_called_class();
			
			// Set up the description manager and confirm its interface
			if(!isset($this->_descriptionManagerClass))
				$this->_descriptionManagerClass = __NAMESPACE__.'\DescriptionManager_Static';
			$this->_descriptionManager = new $this->_descriptionManagerClass($this->__class__);
			if(!$this->_descriptionManager instanceof DescriptionManager)
				throw new Exception('Description management object must be an instance of DescriptionManager');
			
			// Get field names
			$this->_setFields();
			
			// Set the helper strings
			$this->_setStrings();
			
			// Setup the relationships
			$this->_prepareRelationships();
			
			// If it's not already been set, set the default order to PK
			if(!$this->_order)
				$this->_order = "$this->_primaryKey DESC";
			
			// Now we can load any required data
			if($id)
				$this->load($id);
			
		}
		
		public function __get($field) {
			if($field[0] != '_') {
				if(isset($this->$field)) {
					return $this->$field;
				}
				elseif(array_key_exists($field, $this->_relationships)) {
					return $this->$field = $this->_relationships[$field]->load($this->{$this->_relationships[$field]->fieldFrom});
				}
				elseif(array_key_exists($field=rtrim($field,'s'), $this->_relationships)) {
					return $this->$field = $this->_relationships[$field]->loadMany($this->{$this->_relationships[$field]->fieldFrom});
				}
			}
		}
		
		
		/**
		 * Tries to get a reference to a PDO object from the database manager
		 * @uses Database
		 * @throws Exception
		 */
		protected function _getPDO() {
			
			// Get a reference to a database connection, to be used later
			$this->_pdo = Database::getMysqlConncetion(
					$this->_host,
					$this->_username,
					$this->_password,
					$this->_schema
				);
			
			// If we didn't get a connection something is wrong
			if(!$this->_pdo) 
				throw new Exception(
						'PDO connection could not be established',
						Exception::MESSAGE_DATABASE
					);
			
			// We don't need this data anymore, delete it so it never gets dumped to screens.
			$this->_host = $this->_username = $this->_password = '';
			
		}
		
		
		/**
		 * Takes any class variables that don't begin with an underscore and stores
		 * them for mapping to the database. Calls _mapFields();
		 * @throws Exception
		 */
		protected function _setFields() {
			
			// Check the description manager knows about this class, if not tell it
			if(!$this->_descriptionManager->isInitialised()) {
				$this->_descriptionManager->initialise();
				$this->_mapFields();
			}
			
			// Validate the fields exist
			$fieldInfo = $this->_descriptionManager->getTableDescription();
			
			if(empty($fieldInfo))
				throw new Exception("No fields were found in this LazyData Table: $this->__class__", Exception::MESSAGE_BADCODE);
			
			// Extract information about the table, whats its primary key, do we know of any special fields 
			foreach($fieldInfo as $field => $info) {
				/* @var $type FieldDescription */
				if('PRI' == $info->Key)
					$this->_primaryKey = $field;
				if(array_key_exists($field, $this->_specialFields))
					$this->{$this->_specialFields[$field]} = true;
			}
			
		}
		
		
		/**
		 * Maps the class fields to database fields and maps their type 
		 * @throws Exception
		 */
		protected function _mapFields() {
			
			$objectVars = get_object_vars($this);
			
			try {
				$statement = $this->_pdo->prepare("DESCRIBE $this->_table");
				$statement->execute();
				
				// Use the describe to map a type to each field
				while($desc = $statement->fetchObject(__NAMESPACE__.'\FieldDescription')) {
					if(array_key_exists($desc->Field, $objectVars) // If the db field is known, save the data type
							|| array_key_exists($desc->Field, $this->_specialFields)) {      // Special Fields are optional
						$this->_descriptionManager->saveFieldDescription($desc->Field, $desc);
					}
				}
				
			} catch (Exception $e) {
				throw new Exception("PDO couldn't describe $this->_schema.$this->_table", Exception::MESSAGE_DATABASE, $e);
			}
			
		}
		
		/**
		 * Clears the data from this object. Does not clear fields beginning with _
		 */
		public function clearValues() {
			$fields = get_object_vars($this);
			foreach ($fields as $field => $value)
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
			$this->clearValues();
			if($this->checkField($field)) {
				$value = $this->_pdo->quote($value);
				$this->loadWhere("$field = $value");
				if($this->getPrimaryKey())
					return true;
			}
			return false;
		}
		
		/**
		 * Loads a record with a given where.
		 * WARNING: The contents of $where can not be validated here, SQL injection possible.
		 * @param string $where
		 * @param int $count Load this many
		 * @param int $offset Skip this many
		 * @param string $order Order strin
		 * @param bool forceLoad Ignore safe delete and load it if it exists
		 */
		public function loadWhere($where, $count = 1, $offset = 0, $order = null, $forceLoad = false) {
			/* @var $statement \PDOStatement */
			$this->clearValues();
			if($this->_safeDelete && !$forceLoad)
				$where = "($where) AND {$this->getSafeDeleteField()} = 0";
			if(!$order)
				$order = $this->_order;
			$query = "SELECT $this->_fieldsString FROM $this->_table WHERE $where ORDER BY $order LIMIT $offset,$count";
			$statement = $this->_pdo->prepare($query);
			$statement->execute();
			if($row=$statement->fetch(\PDO::FETCH_ASSOC)) {
				$this->setValues($row, false);
			}
		}
		
		/**
		 * Inserts data into database
		 */
		public function insert() {
			/* @var $statement \PDOStatement */
			$this->setDateCreated();
			$query = "INSERT INTO {$this->_table} ($this->_fieldsString) VALUES($this->_valuesString)";
			$statement = $this->_pdo->prepare($query);
			$fields = $this->_descriptionManager->getTableDescription();
			foreach($fields as $field => $type) {
				$value = $this->$field;
				if(is_null($value) && $type->Null == 'NO')
					$value = self::notNullDefault($type->Type);
				$statement->bindValue(":$field", $value, Database::mySqlTypeToPdoType($type->Type));
			}
			$statement->execute();
			$this->{$this->_primaryKey} = $this->_pdo->lastInsertId();
			if($statement->errorCode() > 0) {
				$error = $statement->errorInfo();
				throw new Exception("Error inserting data: $error[2]");
			}
		}
		
		/**
		 * Updates the current entry in the database
		 */
		public function update() {
			/* @var $statement \PDOStatement */
			$this->setDateModified();
			$query = "UPDATE {$this->_table} SET $this->_updateString WHERE $this->_primaryKey = ".(int)$this->getPrimaryKey();
			$statement = $this->_pdo->prepare($query);
			$fields = $this->_descriptionManager->getTableDescription();
			foreach($fields as $field => $type) {
				$value = $this->$field;
				if(is_null($value) && $type->Null == 'NO')
					$value = self::notNullDefault($type->Type);
				$statement->bindValue(":$field", $value, Database::mySqlTypeToPdoType($type->Type));
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
			$fields = $this->_descriptionManager->getTableDescription();
			if($all) {
				foreach($fields as $field => $type) {
					$values[$field] = $this->$field;
				}
			}
			else {
				$allowedFields = $this->_descriptionManager->getTableDescription();
				foreach($fields as $field => $type) {
					if(in_array($field, $allowedFields))
						$values[$field] = $this->$field;
				}
			}
		}
		
		/**
		 * Checks to see if a given field matches ones associated with this object
		 * @param string $field
		 * @return bool
		 */
		public function checkField($field) {
			$allowedFields = $this->_descriptionManager->getTableDescription();
			if(array_key_exists($field, $allowedFields))
				return true;
			return false;
		}
		
		/**
		 * Checks through a list of fields and returns the acceptable ones
		 * @param array $fields
		 * @return array $fields
		 */
		public function checkFields(array $fields) {
			$returnFields = array();
			$allowedFields = $this->_descriptionManager->getTableDescription();
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
			$fields = $this->_descriptionManager->getTableDescription();
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
		public static function get($count = null, $offset = 0, $order = null) {
			return static::getDataWhere('1', $count, $order, $offset);
		}
		
		/**
		 * Gets an array LazyData objects based on the value of a given field
		 * @param string $field
		 * @param mixed $value
		 * @param int $count
		 * @param int $offset
		 * @param string $order
		 * @return array
		 */
		public static function getBy($field, $value, $count = null, $offset = 0, $order = null) {
			$manager = new static();
			if($manager->checkField($field)) {
				$value = $manager->getPDO()->quote($value);
				return static::getDataWhere("$field = $value", $count, $offset, $order);
			}
		}
		
		/**
		 * Get an array of LazyData objects with a WHERE statement
		 * WARNING: The contents of $where can not be validated here, SQL injection possible.
		 * @param string $where
		 * @param int $count
		 * @param int $offset
		 * @param string $order
		 * @return array
		 */
		public static function getWhere($where, $count = null, $offset = 0, $order = null) {
			$datas = array();
			$manager = new static();
			$pdo = $manager->getPDO();
			
			// If we're only getting a few items
			$limit = '';
			if($count)
				$limit = "LIMIT $offset,$count";
			
			// If we aren't changing the order get the default
			if(!$order)
				$order = $manager->getOrder();
			
			$statement = $pdo->prepare("SELECT {$manager->getFieldsString()} FROM {$manager->getTable()} WHERE $where ORDER BY $order $limit");
			$statement->execute();
			while($data = $statement->fetchObject(get_called_class())) {
				if(!($data->_safeDelete && $data->getSafeDeleteValue() > 0)) {
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
			if($info = $this->_descriptionManager->getFieldDescription($fieldName)) {
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
		
		public function getPrimaryKeyName() {
			return $this->_primaryKey;
		}
		
		/**
		 * Returns the date created field name
		 * @return  string
		 */
		public function getSafeCreateField() {
			return array_search('_safeCreate', $this->_specialFields);
		}
		
		/**
		 * Returns the date last modified field name
		 * @return  string
		 */
		public function getSafeModifyField() {
			return array_search('_safeModify', $this->_specialFields);
		}
		
		/**
		 * Returns the date deleted field name
		 * @return  string
		 */
		public function getSafeDeleteField() {
			return array_search('_safeDelete', $this->_specialFields);
		}

		/**
		 * Returns the date created
		 * @return  string
		 */
		public function getSafeCreateValue() {
			return $this->{$this->getSafeCreateField()};
		}

		/**
		 * Returns the date last modified
		 * @return  string
		 */
		public function getSafeModifyValue() {
			return $this->{$this->getSafeModifyField()};
		}

		/**
		 * Returns the date deleted
		 * @return  string
		 */
		public function getSafeDeleteValue() {
			return $this->{$this->getSafeDeleteField()};
		}
		
		/**
		 * Returns the field sting used by PDO
		 * @return string
		 */
		public function getFieldsString() {
			return $this->_fieldsString;
		}
		
		/**
		 * Returns a description of the LazyData table
		 * @return FieldDescription[]
		 */
		public function getTableDescription() {
			return $this->_descriptionManager->getTableDescription();
		}
		
		/**
		 * Prepare relationships
		 */
		protected function _prepareRelationships() {
			foreach($this->_relationships as $key => $relationship) {
				$count = count($relationship); 
				if(3 == $count)
					$this->_relationships[$key] = new Relationship($relationship);
				elseif(5 == $count)
					$this->_relationships[$key] = new RelationshipLink($relationship, $this->_pdo);
				else
					unset($relationship[$key]);
			}
		}
		
		public function cleanField($fieldName) {
			if(isset($this->$fieldName)) {
				$desc = $this->_descriptionManager->getFieldDescription($fieldName);
				if($desc instanceof FieldDescription) {
					$this->$fieldName = $this->_pdo->quote($this->$fieldName, $desc->Type);
					return;
				}
				$this->$fieldName = null;
			}
		}

		/**
		 * Returns an acceptable not null default value for a given type
		 * @param $mySqlType
		 * @return string
		 */
		public function notNullDefault($mySqlType) {
			$phpType = Database::mySqlTypeToPhpType($mySqlType);
			switch($phpType) {
				case 'integer';
				case 'long';
				case 'float';
				case 'double';
					return '0';
				case 'string';
					return '';
			}
			return '';
		}
		
	}