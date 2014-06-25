<?php

namespace test;

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Some unit tests for relations
//
// run: phpunit relation.php

require_once 'testBasis.php';

require_once NEODATA_PATH . 'node.php';
require_once NEODATA_PATH . 'relation.php';
require_once NEODATA_PATH . 'query.php';

class relation extends testBasis {

  public function testCreateRelation() {
    $this->initDB();
    $data1 = array('name' => 'Test user');
    $data2 = array('name' => 'Test user 2');
    $user1 = \neoData\node::create(self::LABEL, $data1);
    $user2 = \neoData\node::create(self::LABEL, $data2);
    \neoData\relation::create(self::LABEL, $user1, $user2);
    $cypher = 'MATCH (a)-[r:' . self::LABEL . ']->(b) where a.name={name} return b';
    // travel along relation to find the node
    $res = \neoData\query::cypher($cypher, $data1);
    $user3 = new \neoData\node();
    $self = $res['data'][0][0]['self'];
    $this->assertGreaterThan(0, \neoData\node::getNodeIdFromSelf($self));
    $user3->initFromQueryResult($res['data'][0][0]);
    $this->assertEquals($user3->getValue('name'), $user2->getValue('name'));
    $this->assertNotEquals($user1->getValue('name'), $user2->getValue('name'));
  }

}

?>
