<?php

namespace test;
// Created: Antti Stenvall (antti@stenvall.fi)
//
// Some unit tests for nodes
//
// run: phpunit node.php

require_once 'testBasis.php';

require_once NEODATA_PATH . 'node.php';

class node extends testBasis {

  /*
   * Exceptions
   */

  /**
   * @expectedException Exception
   * @expectedExceptionCode 204
   *
   */
  public function testExceptionInitNonExistingNode() {
    $this->initDB();
    new \neoData\node(self::LABEL, 'name', 'Should not exist');
  }

  /**
   * @expectedException Exception
   * @expectedExceptionCode 204
   *
   */
  public function testExceptionInitExistingNode() {
    $this->initDB();
    $data = array('name' => 'Test user');
    $user = \neoData\node::create(self::LABEL, $data, 'name');
    $user = \neoData\node::create(self::LABEL, $data, 'name');
  }

  /**
   * @expectedException Exception
   * @expectedExceptionCode 400
   *
   */
  public function testExceptionUpdateNonExistingNode() {
    $this->initDB();
    $data = array('name' => 'Test user');
    $user = new \neoData\node();
    $user->update($data);
  }

  /**
   * @expectedException Exception
   * @expectedExceptionCode 400
   *
   */
  public function testExceptionUnknownProperty() {
    $this->initDB();
    $data = array('name' => 'Test user');
    $user = \neoData\node::create(self::LABEL, $data, 'name');
    $user->getValue('none');
  }

  /**
   * @expectedException Exception
   * @expectedExceptionCode 400
   *
   */
  public function testExceptionBadInit() {
    $this->initDB();
    $node = new \neoData\node();
    $node->initFromQueryResult('dummy');
  }


  public function testCreateNode() {
    $this->initDB();
    $data = array('name' => 'Test user');
    $user = \neoData\node::create(self::LABEL, $data);
    $user2 = new \neoData\node(self::LABEL, 'name', $data['name']);
    $this->assertEquals($user->getNodeId(), $user2->getNodeId());
  }

}

?>
