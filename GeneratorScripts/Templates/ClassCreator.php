<?='<?php'?>
    /**
     * LazyData class
     */
    class <?=$table?>
    {
        <?php foreach($fields as $field) { ?>
        /**
         * <?=$field->name?>
         * @var <?=$field->type?>
         */
        <?=$field->field?>

         <?php } ?>
    }