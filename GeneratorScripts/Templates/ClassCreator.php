<?='<?php'?> 

	use <?=$config->namespace?>;

	/**
	 * LazyData class
	 */
	class <?=$classname?> extends <?=$config->dbSchema?>Abstract {
		<?php foreach($fields as $field) { ?>

		/**
		 * <?=$field->name?> 
		 * @var <?=$field->type?> 
		 */
		public <?=$field->field?>;

		<?php } ?>

	}