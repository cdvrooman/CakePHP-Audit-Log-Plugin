<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class AuditableTranslatedItemFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'AuditableTranslatedItem'
 */
	public $name = 'AuditableTranslatedItem';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'auditable_translated_article_id' => array('type' => 'integer'),
		'slug' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('auditable_translated_article_id' => 1, 'slug' => 'first_translated'),
		array('auditable_translated_article_id' => 1, 'slug' => 'second_translated'),
		array('auditable_translated_article_id' => 1, 'slug' => 'third_translated')
	);
}
