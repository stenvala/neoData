<?php

namespace test;

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Parent for unit tests

define('NEODATA_PATH', '../');

class testBasis extends \PHPUnit_Framework_TestCase {

  const LABEL = 'PHPUNIT_TEST_CYPHER';

  public function __construct() {
    $this->initDB();
  }

  public function __destruct() {
    $this->clearDB();
  }

  protected function initDB() {
    $this->clearDB();
  }

  protected function clearDB() {
    \neoData\query::cypher('MATCH (n:' . self::LABEL .
      ') OPTIONAL MATCH (n:' . self::LABEL . ')-[r:' . self::LABEL . ']-() DELETE n,r');
  }

}

?>
