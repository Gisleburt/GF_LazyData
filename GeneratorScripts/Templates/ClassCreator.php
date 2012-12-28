<?='<?php'?> 

	namespace <?=$config->namespace?>;

	/**
	 * LazyData class
	 */
	class <?=$classname?> extends <?=$abstractName?> {
		<?php foreach($fields as $field) { ?>

		/**
		 * <?=$field->name?> 
		 * @var <?=$field->type?> 
		 */
		public $<?=$field->field?>;

		<?php } ?>

	}
