<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once CORE_PATH . 'Cake' . DS . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * TranslateTestModel class.
 *
 * @package       Cake.Test.Case.Model
 */
class AuditableTranslateTestModel extends TranslateTestModel {

/**
 * name property
 *
 * @var string 'AuditableTranslateTestModel'
 */
	public $name = 'AuditableTranslateTestModel';

/**
 * useTable property
 *
 * @var string 'i18n'
 */
	public $useTable = 'i18n';

/**
 * displayField property
 *
 * @var string 'field'
 */
	public $displayField = 'field';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array(
		'AuditLog.Auditable'
	);
}

/**
 * AuditableTranslatedItem class.
 *
 * @package       Cake.Test.Case.Model
 */
class AuditableTranslatedItem extends TranslatedItem {

/**
 * name property
 *
 * @var string 'AuditableTranslatedItem'
 */
	public $name = 'AuditableTranslatedItem';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array(
		'Translate' => array('content', 'title'),
		'AuditLog.Auditable'
	);

/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 */
	public $translateModel = 'AuditableTranslateTestModel';

}

/**
 * AuditableTranslatedArticle class.
 *
 * @package       Cake.Test.Case.Model
 */
class AuditableTranslatedArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuditableTranslatedArticle'
 */
	public $name = 'AuditableTranslatedArticle';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array(
		'Translate' => array('title', 'body'),
		'AuditLog.Auditable'
	);

/**
 * translateModel property
 *
 * @var string 'AuditableTranslateArticleModel'
 */
	public $translateModel = 'AuditableTranslateArticleModel';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('User');

/**
 * belongsTo property
 *
 * @var array
 */
	public $hasMany = array('AuditableTranslatedItem');

}

/**
 * AuditableTranslateArticleModel class.
 *
 * @package       Cake.Test.Case.Model
 */
class AuditableTranslateArticleModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuditableTranslateArticleModel'
 */
	public $name = 'AuditableTranslateArticleModel';

/**
 * useTable property
 *
 * @var string 'article_i18n'
 */
	public $useTable = 'article_i18n';

/**
 * displayField property
 *
 * @var string 'field'
 */
	public $displayField = 'field';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array(
		'AuditLog.Auditable'
	);

}

/**
 * TranslateBehaviorTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TranslateBehaviorTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool false
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
    'plugin.audit_log.audit',
    'plugin.audit_log.audit_delta',
		'plugin.audit_log.auditable_translated_item',
		'plugin.audit_log.auditable_translate',
		'plugin.audit_log.auditable_translated_article',
		'plugin.audit_log.auditable_translate_article',
		'core.user',
	);

/**
 * testSaveCreate method
 *
 * @return void
 */
	public function testSaveCreate() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem', 'AuditableTranslatedArticle');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'spa';
		$data = array(
			'slug' => 'fourth_translated',
			'title' => 'Leyenda #4',
			'content' => 'Contenido #4',
			'auditable_translated_article_id' => 1,
		);
		$TestModel->create($data);
		$TestModel->save();
		$result = $TestModel->read();
		$expected = array('AuditableTranslatedItem' => array_merge($data, array('id' => $TestModel->id, 'locale' => 'spa')));
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAssociatedCreate method
 *
 * @return void
 */
	public function testSaveAssociatedMultipleLocale() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$data = array(
			'slug' => 'fourth_translated',
			'title' => array(
				'eng' => 'Title #4',
				'spa' => 'Leyenda #4',
			),
			'content' => array(
				'eng' => 'Content #4',
				'spa' => 'Contenido #4',
			),
			'auditable_translated_article_id' => 1,
		);
		$TestModel->create();
		$TestModel->saveAssociated($data);

		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$TestModel->locale = array('eng', 'spa');
		$result = $TestModel->read();
	}

/**
 * Test that saving only some of the translated fields allows the record to be found again.
 *
 * @return void
 */
	public function testSavePartialFields() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'spa';
		$data = array(
			'slug' => 'fourth_translated',
			'title' => 'Leyenda #4',
		);
		$TestModel->create($data);
		$TestModel->save();
		$result = $TestModel->read();
		$expected = array(
			'AuditableTranslatedItem' => array(
				'id' => $TestModel->id,
				'auditable_translated_article_id' => null,
				'locale' => 'spa',
				'content' => '',
			) + $data
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that all fields are create with partial data + multiple locales.
 *
 * @return void
 */
	public function testSavePartialFieldMultipleLocales() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'eng';
		$data = array(
			'slug' => 'fifth_translated',
			'title' => array('eng' => 'Title #5', 'spa' => 'Leyenda #5'),
		);
		$TestModel->create($data);
		$TestModel->save();
		$TestModel->unbindTranslation();

		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, $TestModel->id);
		$expected = array(
			'AuditableTranslatedItem' => array(
				'id' => '4',
				'auditable_translated_article_id' => null,
				'slug' => 'fifth_translated',
				'locale' => 'eng',
				'title' => 'Title #5',
				'content' => ''
			),
			'Title' => array(
				0 => array(
					'id' => '19',
					'locale' => 'eng',
					'model' => 'AuditableTranslatedItem',
					'foreign_key' => '4',
					'field' => 'title',
					'content' => 'Title #5'
				),
				1 => array(
					'id' => '20',
					'locale' => 'spa',
					'model' => 'AuditableTranslatedItem',
					'foreign_key' => '4',
					'field' => 'title',
					'content' => 'Leyenda #5'
				)
			),
			'Content' => array(
				0 => array(
					'id' => '21',
					'locale' => 'eng',
					'model' => 'AuditableTranslatedItem',
					'foreign_key' => '4',
					'field' => 'content',
					'content' => ''
				),
				1 => array(
					'id' => '22',
					'locale' => 'spa',
					'model' => 'AuditableTranslatedItem',
					'foreign_key' => '4',
					'field' => 'content',
					'content' => ''
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveUpdate method
 *
 * @return void
 */
	public function testSaveUpdate() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'spa';
		$oldData = array('slug' => 'fourth_translated', 'title' => 'Leyenda #4', 'auditable_translated_article_id' => 1);
		$TestModel->create($oldData);
		$TestModel->save();
		$id = $TestModel->id;
		$newData = array('id' => $id, 'content' => 'Contenido #4');
		$TestModel->create($newData);
		$TestModel->save();
		$result = $TestModel->read(null, $id);
		$expected = array('AuditableTranslatedItem' => array_merge($oldData, $newData, array('locale' => 'spa')));
		$this->assertEquals($expected, $result);
	}

/**
 * testMultipleCreate method
 *
 * @return void
 */
/*
	public function testMultipleCreate() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'deu';
		$data = array(
			'slug' => 'new_translated',
			'title' => array('eng' => 'New title', 'spa' => 'Nuevo leyenda'),
			'content' => array('eng' => 'New content', 'spa' => 'Nuevo contenido')
		);
		$TestModel->create($data);
		$TestModel->save();

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$TestModel->locale = array('eng', 'spa');

		$result = $TestModel->read();
		$expected = array(
			'AuditableTranslatedItem' => array(
				'id' => 4,
				'slug' => 'new_translated',
				'locale' => 'eng',
				'title' => 'New title',
				'content' => 'New content',
				'auditable_translated_article_id' => null,
			),
			'Title' => array(
				array('id' => 21, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'New title'),
				array('id' => 22, 'locale' => 'spa', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'Nuevo leyenda')
			),
			'Content' => array(
				array('id' => 19, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'New content'),
				array('id' => 20, 'locale' => 'spa', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'Nuevo contenido')
			)
		);
		$this->assertEquals($expected, $result);
	}
*/

/**
 * testMultipleUpdate method
 *
 * @return void
 */
	public function testMultipleUpdate() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = 'notEmpty';
		$data = array('AuditableTranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'New Title #1', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$TestModel->save($data);

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, 1);
		$expected = array(
			'AuditableTranslatedItem' => array(
				'id' => '1',
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'New Title #1',
				'content' => 'New Content #1',
				'auditable_translated_article_id' => 1,
			),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'New Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Neue Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Novy Titulek #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'New Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Neue Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Novy Obsah #1')
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->unbindTranslation($translations);
		$TestModel->bindTranslation(array('title', 'content'), false);
	}

/**
 * testMixedCreateUpdateWithArrayLocale method
 *
 * @return void
 */
	public function testMixedCreateUpdateWithArrayLocale() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = array('cze', 'deu');
		$data = array('AuditableTranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'Updated Title #1', 'spa' => 'Nuevo leyenda #1'),
			'content' => 'Upraveny obsah #1'
		));
		$TestModel->create();
		$TestModel->save($data);

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, 1);
		$result['Title'] = Hash::sort($result['Title'], '{n}.id', 'asc');
		$result['Content'] = Hash::sort($result['Content'], '{n}.id', 'asc');
		$expected = array(
			'AuditableTranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'cze',
				'title' => 'Titulek #1',
				'content' => 'Upraveny obsah #1',
				'auditable_translated_article_id' => 1,
			),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Updated Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1'),
				array('id' => 19, 'locale' => 'spa', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Nuevo leyenda #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'AuditableTranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Upraveny obsah #1')
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that saveAll() works with hasMany associations that contain
 * translations.
 *
 * @return void
 */
	public function testSaveAllTranslatedAssociations() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslateArticle', 'AuditableTranslatedItem', 'AuditableTranslatedArticle', 'User');

		$Model = new AuditableTranslatedArticle();
		$Model->locale = 'eng';

		$data = array(
			'TranslatedArticle' => array(
				'id' => 4,
				'user_id' => 1,
				'published' => 'Y',
				'title' => 'Title (eng) #1',
				'body' => 'Body (eng) #1'
			),
			'AuditableTranslatedItem' => array(
				array(
					'slug' => '',
					'title' => 'Nuevo leyenda #1',
					'content' => 'Upraveny obsah #1'
				),
				array(
					'slug' => '',
					'title' => 'New Title #2',
					'content' => 'New Content #2'
				),
			)
		);
		$result = $Model->saveAll($data);
		$this->assertTrue($result);

		$result = $Model->AuditableTranslatedItem->find('all', array(
			'conditions' => array('auditable_translated_article_id' => $Model->id)
		));
		$this->assertEquals($data['AuditableTranslatedItem'][0]['title'], $result[0]['AuditableTranslatedItem']['title']);
		$this->assertEquals($data['AuditableTranslatedItem'][1]['title'], $result[1]['AuditableTranslatedItem']['title']);
	}

/**
 * testValidation method
 *
 * @return void
 */
	public function testValidation() {
		$this->loadFixtures('Audit', 'AuditDelta', 'AuditableTranslate', 'AuditableTranslatedItem');

		$TestModel = new AuditableTranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array(
			'AuditableTranslatedItem' => array(
				'id' => 1,
				'title' => array('eng' => 'New Title #1', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
				'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
			)
		);
		$TestModel->create();
		$this->assertFalse($TestModel->save($data));
		$this->assertEquals(array('This field cannot be left blank'), $TestModel->validationErrors['title']);

		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array('AuditableTranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'Only this title', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$result = $TestModel->save($data);
		$this->assertFalse(empty($result));
	}

}