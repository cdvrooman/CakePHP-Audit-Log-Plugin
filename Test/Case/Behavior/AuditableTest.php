<?php

App::uses( 'Model', 'Model' );
App::uses( 'AppModel', 'Model' );

/**
 * Article class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Article extends CakeTestModel {
  public $name = 'Article';
  public $actsAs = array(
    'AuditLog.Auditable' => array(
      'ignore' => array( 'ignored_field' ),
    )
  );
  public $belongsTo = array( 'Author' );
}

/**
 * Author class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Author extends CakeTestModel {
  public $name = 'Author';
  public $actsAs = array(
    'AuditLog.Auditable'
  );
  public $hasMany = array( 'Article' );
}

class Audit extends CakeTestModel {
  public $hasMany = array(
    'AuditDelta'
  );
}

class AuditDelta extends CakeTestModel {
  public $belongsTo = array(
    'Audit'
  );
}

/**
 * Author3 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Author3 extends CakeTestModel {
  public $name = 'Author3';
  public $actsAs = array(
    'AuditLog.Auditable'
  );

	public $callbackOriginal = array();

	public $callbackUpdates = array();

	public $callbackAuditId = array();

	public $callbackAuditProperty = array();

  public function currentUser() {
  	return array('id' => 10);
  }

  public function afterAuditCreate( Model $Model ) {
		$this->saveField('updated', '2012-11-10 09:08:07');
  }

  public function afterAuditUpdate( Model $Model, $original, $updates, $auditId ) {
		$this->callbackOriginal = $original;
		$this->callbackUpdates = $updates;
		$this->callbackAuditId = $auditId;
  }

  public function afterAuditProperty( Model $Model, $deltaProperty, $original, $delta ) {
		$this->callbackAuditProperty[] = array(
			'deltaPropertyName' => $deltaProperty,
			'original' => $original,
			'delta' => $delta
		);
  }
}

/**
 * Author4 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Author4 extends CakeTestModel {
  public $name = 'Author4';
  public $actsAs = array(
    'AuditLog.Auditable'
  );

  public function current_user() {
  	return array('id' => 20);
  }
}

/**
 * AuditableBehavior test class.
 */
class AuditableBehaviorTest extends CakeTestCase {
  /**
   * Fixtures associated with this test case
   *
   * @var array
   * @access public
   */
  public $fixtures = array(
    'plugin.audit_log.article',
    'plugin.audit_log.author',
    'plugin.audit_log.audit',
    'plugin.audit_log.audit_delta',
    'plugin.audit_log.author3',
    'plugin.audit_log.author4',
  );

  /**
   * Method executed before each test
   *
   * @access public
   */
  public function setUp() {
    $this->Article = ClassRegistry::init( 'Article' );
    $this->Author3 = ClassRegistry::init( 'Author3' );
    $this->Author4 = ClassRegistry::init( 'Author4' );
  }

  /**
   * Method executed after each test
   *
   * @access public
   */
  public function tearDown() {
    unset( $this->Article, $this->Author3, $this->Author4 );

    ClassRegistry::flush();
  }

  /**
   * Test the action of creating a new record.
   *
   * @todo  Test HABTM save
   */
  public function testCreate() {
    $new_article = array(
      'Article' => array(
        'user_id'   => 1,
        'author_id' => 1,
        'title'     => 'First Test Article',
        'body'      => 'First Test Article Body',
        'published' => 'N',
      ),
    );

    $this->Article->save( $new_article );
    $audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $article = json_decode( $audit['Audit']['json_object'], true );

    $deltas = ClassRegistry::init( 'AuditDelta' )->find(
      'all',
      array(
        'recursive' => -1,
        'conditions' => array( 'AuditDelta.audit_id' => $audit['Audit']['id'] ),
      )
    );

    # Verify the audit record
    $this->assertEquals( 1, $article['Article']['user_id'] );
    $this->assertEquals( 'First Test Article', $article['Article']['title'] );
    $this->assertEquals( 'N', $article['Article']['published'] );

    #Verify that no delta record was created.
    $this->assertEmpty( $deltas ) ;

   # Verify explicitly disabling the behavior and afterSave.
		$this->Article->Behaviors->Auditable->enabled = false;

    $new_article = array(
      'Article' => array(
        'user_id'   => 99,
        'author_id' => 1,
        'title'     => 'Do Not Publish Test Article',
        'body'      => 'Do Not Publish Test Article Body',
        'published' => 'N',
      ),
    );

		$this->Article->create();
    $this->Article->save( $new_article );
    $audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $this->assertEmpty( $audit );
		$this->Article->Behaviors->Auditable->enabled = true;
  }

  /**
   * Test saving multiple records with Model::saveAll()
   */
  public function testSaveAll() {
    # TEST A MODEL AND A SINGLE ASSOCIATED MODEL
    $data = array(
      'Article' => array(
        'user_id'   => 1,
        'title'     => 'Rob\'s Test Article',
        'body'      => 'Rob\'s Test Article Body',
        'published' => 'Y',
      ),
      'Author' => array(
        'first_name' => 'Rob',
        'last_name' => 'Wilkerson',
      ),
    );

    $this->Article->saveAll( $data );
    $article_audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $article = json_decode( $article_audit['Audit']['json_object'], true );

    # Verify the audit record
    $this->assertEquals( 1, $article['Article']['user_id'] );
    $this->assertEquals( 'Rob\'s Test Article', $article['Article']['title'] );
    $this->assertEquals( 'Y', $article['Article']['published'] );

    # Verify that no delta record was created.
    $this->assertTrue( !isset( $article_audit['AuditDelta'] ) );

    $author_audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Author',
          'Audit.entity_id' => $this->Article->Author->getLastInsertId()
        )
      )
    );
    $author = json_decode( $author_audit['Audit']['json_object'], true );

    # Verify the audit record
    $this->assertEquals( $article['Article']['author_id'], $author['Author']['id'] );
    $this->assertEquals( 'Rob', $author['Author']['first_name'] );

    # Verify that no delta record was created.
    $this->assertTrue( !isset( $author_audit['AuditDelta'] ) );

    # TEST MULTIPLE RECORDS OF ONE MODEL

    $data = array(
        array(
          'Article' => array(
            'user_id'   => 1,
            'author_id' => 1,
            'title'     => 'Multiple Save 1 Title',
            'body'      => 'Multiple Save 1 Body',
            'published' => 'Y',
          ),
        ),
        array(
          'Article' => array(
            'user_id'       => 2,
            'author_id'     => 2,
            'title'         => 'Multiple Save 2 Title',
            'body'          => 'Multiple Save 2 Body',
            'published'     => 'N',
            'ignored_field' => 1,
          )
        ),
        array(
          'Article' => array(
            'user_id'   => 3,
            'author_id' => 3,
            'title'     => 'Multiple Save 3 Title',
            'body'      => 'Multiple Save 3 Body',
            'published' => 'Y',
          )
        ),
    );
    $this->Article->create();
    $this->Article->saveAll( $data );

    # Retrieve the audits for the last 3 articles saved
    $audits = ClassRegistry::init( 'Audit' )->find(
      'all',
      array(
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
        ),
        'order' => array( 'Audit.entity_id DESC' ),
        'limit' => 3
      )
    );

    $article_1 = json_decode( $audits[2]['Audit']['json_object'], true );
    $article_2 = json_decode( $audits[1]['Audit']['json_object'], true );
    $article_3 = json_decode( $audits[0]['Audit']['json_object'], true );

    # Verify the audit records
    $this->assertEquals( 1, $article_1['Article']['user_id'] );
    $this->assertEquals( 'Multiple Save 1 Title', $article_1['Article']['title'] );
    $this->assertEquals( 'Y', $article_1['Article']['published'] );

    $this->assertEquals( 2, $article_2['Article']['user_id'] );
    $this->assertEquals( 'Multiple Save 2 Title', $article_2['Article']['title'] );
    $this->assertEquals( 'N', $article_2['Article']['published'] );

    $this->assertEquals( 3, $article_3['Article']['user_id'] );
    $this->assertEquals( 'Multiple Save 3 Title', $article_3['Article']['title'] );
    $this->assertEquals( 'Y', $article_3['Article']['published'] );

    # Verify that no delta records were created.
    $this->assertEmpty( $audits[0]['AuditDelta'] );
    $this->assertEmpty( $audits[1]['AuditDelta'] );
    $this->assertEmpty( $audits[2]['AuditDelta'] );
  }

  /**
   * Test editing an existing record.
   *
   * @todo  Test change to ignored field
   * @todo  Test HABTM save
   */
  public function testEdit() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );

    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'First Test Article',
        'body'          => 'First Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N',
      ),
    );

    # TEST SAVE WITH SINGLE PROPERTY UPDATE

   	$this->Article->save( $new_article );
   	$this->Article->saveField( 'title', 'First Test Article (Edited)' );

    $audit_records = $this->Audit->find(
      'all',
      array(
        'recursive' => 0,
        'conditions' => array(
          'Audit.model' => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $delta_records = $this->AuditDelta->find(
      'all',
      array(
        'recursive' => -1,
        'conditions' => array( 'AuditDelta.audit_id' => Set::extract( '/Audit/id', $audit_records ) ),
      )
    );

    $create_audit = Set::extract( '/Audit[event=CREATE]', $audit_records );
    $update_audit = Set::extract( '/Audit[event=EDIT]', $audit_records );

    # There should be 1 CREATE and 1 EDIT record
    $this->assertEquals( 2, count( $audit_records ) );

    # There should be one audit record for each event.
    $this->assertEquals( 1, count( $create_audit ) );
    $this->assertEquals( 1, count( $update_audit ) );

    # Only one property was changed
    $this->assertEquals( 1, count( $delta_records ) );

    $delta = array_shift( $delta_records );
    $this->assertEquals( 'First Test Article', $delta['AuditDelta']['old_value'] );
    $this->assertEquals( 'First Test Article (Edited)', $delta['AuditDelta']['new_value'] );

    # TEST UPDATE OF MULTIPLE PROPERTIES
    # Pause to simulate a gap between edits
    # This also allows us to retrieve the last edit for the next set
    # of tests.
    $this->Article->create(); # Clear the article id so we get a new record.
    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'Second Test Article',
        'body'          => 'Second Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N',
      ),
    );
    $this->Article->save( $new_article );

    $updated_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'Second Test Article (Newly Edited)',
        'body'          => 'Second Test Article Body (Also Edited)',
        'ignored_field' => 0,
        'published'     => 'Y',
      ),
    );
    $this->Article->save( $updated_article );

    $last_audit = $this->Audit->find(
      'first',
      array(
        'contain'    => array( 'AuditDelta' ),
        'conditions' => array(
          'Audit.event'     => 'EDIT',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->id
        ),
        'order' => 'Audit.created DESC',
      )
    );

    # There are 4 changes, but one to an ignored field
    $this->assertEquals( 3, count( $last_audit['AuditDelta'] ) );
    $expected = Set::extract( '/AuditDelta[property_name=title]/old_value', $last_audit );
	  $this->assertEquals( 'Second Test Article', array_shift( $expected ) );
		$expected = Set::extract( '/AuditDelta[property_name=title]/new_value', $last_audit );
    $this->assertEquals( 'Second Test Article (Newly Edited)', array_shift( $expected ) );

    $expected = Set::extract( '/AuditDelta[property_name=body]/old_value', $last_audit );
    $this->assertEquals( 'Second Test Article Body', array_shift( $expected ) );
		$expected = Set::extract( '/AuditDelta[property_name=body]/new_value', $last_audit );
    $this->assertEquals( 'Second Test Article Body (Also Edited)', array_shift( $expected ) );

		$expected = Set::extract( '/AuditDelta[property_name=published]/old_value', $last_audit );
    $this->assertEquals( 'N', array_shift( $expected ) );
    $expected = Set::extract( '/AuditDelta[property_name=published]/new_value', $last_audit );
    $this->assertEquals( 'Y', array_shift( $expected ) );

    # No delta should be reported against the ignored field.
		$expected = Set::extract( '/AuditDelta[property_name=ignored_field]', $last_audit );
    $this->assertIdentical( array(), $expected );

   # Verify explicitly disabling the behavior prevents the creation of a delta.
		$this->Article->create();

    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'No Delta Test Article',
        'body'          => 'No Delta Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N',
      ),
    );

    $this->Article->save( $new_article );

		$this->Article->Behaviors->Auditable->enabled = false;

    $this->Article->saveField( 'title', 'No Delta (Edited)' );

    $audit_records = $this->Audit->find(
      'all',
      array(
        'recursive' => 0,
        'conditions' => array(
          'Audit.model' => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $delta_records = $this->AuditDelta->find(
      'all',
      array(
        'recursive' => -1,
        'conditions' => array( 'AuditDelta.audit_id' => Set::extract( '/Audit/id', $audit_records ) ),
      )
    );

    $create_audit = Set::extract( '/Audit[event=CREATE]', $audit_records );
    $update_audit = Set::extract( '/Audit[event=EDIT]', $audit_records );

    # There should be only 1 CREATE record
    $this->assertEquals( 1, count( $audit_records ) );

    # There should be one audit record for the create event.
    $this->assertEquals( 1, count( $create_audit ) );
    $this->assertEmpty( $update_audit );

    # No delta record should have been saved
    $this->assertEmpty( $delta_records );
		$this->Article->Behaviors->Auditable->enabled = true;
  }

  public function testIgnoredField() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );

    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'First Test Article',
        'body'          => 'First Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N',
      ),
    );

    # TEST NO AUDIT RECORD IF ONLY CHANGE IS IGNORED FIELD

    $this->Article->save( $new_article );
    $this->Article->saveField( 'ignored_field', '5' );

    $last_audit = $this->Audit->find(
      'count',
      array(
        'contain'    => array( 'AuditDelta' ),
        'conditions' => array(
          'Audit.event'     => 'EDIT',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->id
        ),
        'order' => 'Audit.created DESC',
      )
    );

    $this->assertEquals( 0, $last_audit );
  }

  public function testDelete() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );
    $article = $this->Article->find(
      'first',
      array(
        'contain' => false,
        'order'   => array( 'rand()' ),
      )
    );

    $id = $article['Article']['id'];

    $this->Article->delete( $id );

    $last_audit = $this->Audit->find(
      'all',
      array(
        //'contain'    => array( 'AuditDelta' ), <-- What does this solve?
        'conditions' => array(
          'Audit.event'     => 'DELETE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $id,
        ),
        'order' => 'Audit.created DESC',
      )
    );

    $this->assertEquals( 1, count( $last_audit ) );

   # Verify explicitly disabling the behavior and afterDelete.
		$this->Article->Behaviors->Auditable->enabled = false;

    $article = $this->Article->find(
      'first',
      array(
        'contain' => false,
        'order'   => array( 'rand()' ),
      )
    );

    $id = $article['Article']['id'];

    $this->Article->delete( $id );

    $last_audit = $this->Audit->find(
      'all',
      array(
        'conditions' => array(
          'Audit.event'     => 'DELETE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $id,
        ),
        'order' => 'Audit.created DESC',
      )
    );
    $this->assertEmpty( $last_audit );
		$this->Article->Behaviors->Auditable->enabled = true;
  }

	/**
	 * testCurrentUser method
	 *
	 * @return	void
	 * @access	public
	 */
	public function testCurrentUser() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );

		# Creating a record:

		# Add a new author
    $data = array(
      'Author3' => array(
        'first_name' => 'Rob',
        'last_name' => 'Wilkerson',
      ),
    );
		$this->Author3->save($data);

		$id3 = $this->Author3->getInsertID();

    $audit = $this->Audit->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Author3',
          'Audit.entity_id' => $id3
        )
      )
    );

    # Verify the audit record
    $this->assertEquals( '10', $audit['Audit']['source_id'] );

		# Add a new author
    $data = array(
      'Author4' => array(
        'first_name' => 'Rob',
        'last_name' => 'Wilkerson',
      ),
    );
		$this->Author4->save($data);

		$id4 = $this->Author4->getInsertID();

    $audit = $this->Audit->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Author4',
          'Audit.entity_id' => $id4
        )
      )
    );

    # Verify the audit record
    $this->assertEquals( '20', $audit['Audit']['source_id'] );

    # Deleting a record:
    $this->Author3->delete( $id3 );

    $last_audit = $this->Audit->find(
      'all',
      array(
        'conditions' => array(
          'Audit.event'     => 'DELETE',
          'Audit.model'     => 'Author3',
          'Audit.entity_id' => $id3,
        ),
        'order' => 'Audit.created DESC',
      )
    );
    $this->assertEquals( 1, count( $last_audit ) );
    $this->assertEquals( '10', $last_audit[0]['Audit']['source_id'] );

    $this->Author4->delete( $id4 );

    $last_audit = $this->Audit->find(
      'all',
      array(
        'conditions' => array(
          'Audit.event'     => 'DELETE',
          'Audit.model'     => 'Author4',
          'Audit.entity_id' => $id4,
        ),
        'order' => 'Audit.created DESC',
      )
    );
    $this->assertEquals( 1, count( $last_audit ) );
    $this->assertEquals( '20', $last_audit[0]['Audit']['source_id'] );
	}


	/**
	 * testAfterAuditCallbacks method
	 *
	 * @return	void
	 * @access	public
	 */
	public function testAfterAuditCallbacks() {
		# Add a new author
    $data = array(
      'Author3' => array(
        'first_name' => 'Rob',
        'last_name' => 'Wilkerson',
      ),
    );
		$this->Author3->save($data);

		$id = $this->Author3->getInsertID();

		# Assert the updated field has been modified by the afterAuditCreate method
		$result = $this->Author3->field('updated');
		$this->assertEquals($result, '2012-11-10 09:08:07');

		# Update the existing author
    $data = array(
      'Author3' => array(
      	'id' => $id,
        'first_name' => 'Robert',
        'last_name' => 'Wilkerson',
      ),
    );
		$this->Author3->save($data);

		# Assert the model's afterAuditUpdate method was called:
		$original = $this->Author3->callbackOriginal;
		unset($original['Author3']['created']);
		$expectedOriginal = array(
			'Author3' => array(
				'id' => '1',
				'first_name' => 'Rob',
				'last_name' => 'Wilkerson',
				'updated' => '2012-11-10 09:08:07'
			)
		);
		$this->assertEquals($original, $expectedOriginal);

		$updates = $this->Author3->callbackUpdates;
		$expectedUpdate = array(
			0 => array(
				'AuditDelta' => array(
					'property_name' => 'first_name',
					'old_value' => 'Rob',
					'new_value' => 'Robert'
				)
			)
		);
		$this->assertEquals($updates, $expectedUpdate);

		$auditId = $this->Author3->callbackAuditId;
		$expectedAuditId = ClassRegistry::init( 'Audit' )->getInsertID();
		$this->assertEquals($auditId, $expectedAuditId);

		# Assert the model's afterAuditProperty method was called
		$expectedProperty = array(
			0 => array(
				'deltaPropertyName' => 'first_name',
				'original' => 'Rob',
				'delta' => 'Robert'
			)
		);
		$this->assertEquals($this->Author3->callbackAuditProperty, $expectedProperty);
	}
}