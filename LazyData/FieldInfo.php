<?php

	/**
	 * Stores field information on table fields
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */

	class LazyData_FieldInfo {
		
		public $Field;
		public $Type;
		public $Size;
		public $Null;
		public $Key;
		public $Default;
		public $Extra;
		
		public function __construct(array $args = null) {
			$this->_clarify();
		}
		
		/**
		 * Breaks Type into Type and Size, for example TINYINT(1) becomes Type TINYINT, Size 1
		 */
		protected function _clarify() {
			$size = preg_filter('/\D/', '', $this->Type);
			if($size)
				$this->Size = $size;
			$type = preg_filter('/\(.*\)/', '', $this->Type);
			if($type)
				$this->Type = $type;
		}
		
	}