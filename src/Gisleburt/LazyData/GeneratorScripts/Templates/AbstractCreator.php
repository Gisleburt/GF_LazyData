<?='<?php' ?>

	namespace <?=$config->namespace?>;

	use Gisleburt\LazyData\LazyData;

	/**
	 * LazyData Database Class
	 */
	abstract class <?=$classname?> extends LazyData  {

		protected $_host = '<?=$config->dbHost?>';
		protected $_username = '<?=$config->dbUsername?>';
		protected $_password = '<?=$config->dbPassword?>';
		protected $_schema = '<?=$config->dbSchema?>';

	}