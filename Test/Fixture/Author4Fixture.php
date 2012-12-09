<?php

class Author4Fixture extends CakeTestFixture {
  public $name = 'Author4';

  public $fields = array(
    'id'         => array('type' => 'integer', 'key' => 'primary'),
    'first_name' => array('type' => 'string', 'null' => false),
    'last_name'  => array('type' => 'string', 'null' => false),
    'created'    => 'datetime',
    'updated'    => 'datetime'
  );

  /**
   * records property
   *
   * @public array
   * @access public
   */
  public $records = array();
}
