<?php
$xpdo_meta_map['siSession']= array (
  'package' => 'sendit',
  'version' => '1.1',
  'table' => 'si_sessions',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'session_id' => NULL,
    'class_name' => NULL,
    'data' => NULL,
    'createdon' => NULL,
  ),
  'fieldMeta' => 
  array (
    'session_id' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'index' => 'index',
    ),
    'class_name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
    ),
    'data' => 
    array (
      'dbtype' => 'json',
      'phptype' => 'string',
      'null' => true,
    ),
    'createdon' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => false,
      'index' => 'index',
    ),
  ),
  'indexes' => 
  array (
    'app_session' => 
    array (
      'alias' => 'app_session',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'session_id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
        'class_name' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'createdon' => 
    array (
      'alias' => 'createdon',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'createdon' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
