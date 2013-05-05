<?='<?php'?> 

	namespace <?=$config->namespace?>;

	/**
	 * LazyData class
	 */
	class <?=$classname?> extends <?=$abstractName?> {
	
		/**
		 * The table this LazyData object represents
		 * @var string
		 */
		protected $_table = '<?=$table?>';
	
		<?php foreach($fields as $field) { ?>

		/**
		 * <?=$field->name?> 
		 * @var <?=$field->type?> 
		 */
		public $<?=$field->field?>;

		<?php } ?>

	}
