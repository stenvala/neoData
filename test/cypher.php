<?php

namespace test;

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Some unit tests for boilerplate cypher queries
//
// run: phpunit relation.php

require_once 'testBasis.php';

require_once NEODATA_PATH . 'query.php';

class relation extends testBasis {

  public function testCreateNode() {
    $this->initDB();
    $data = array('name' => 'Test user', 'temp' => 'data');
    \neoData\query::createNode(self::LABEL, $data);
    $data2 = \neoData\query::getNodeData(self::LABEL, $data);
    $this->assertEquals($data['name'], $data2['name']);
  }

  public function testCreateRelation() {
    $this->initDB();
    $data1 = array('name' => 'Test user', 'temp' => 'data');
    $data2 = array('name' => 'Other User', 'temp' => 'data');
    $node1 = \neoData\query::createNode(self::LABEL, $data1);
    $id1 = \neoData\query::getNodeIdFromData($node1);
    $node2 = \neoData\query::createNode(self::LABEL, $data2);
    $id2 = \neoData\query::getNodeIdFromData($node2);
    \neoData\query::createRelation(self::LABEL,$id1, $id2);
    // get node data by following the relation
    $cypher = 'MATCH (a)-[r:' . self::LABEL . ']->(b) where a.name={name} return b';
    $node3 = \neoData\query::cypher($cypher, array('name' => $data1['name']));
    $data3 = \neoData\query::getNodeData($node3);
    $this->assertEquals($data3['name'], $data2['name']);
    $this->assertNotEquals($data3['name'], $data1['name']);
  }

  // TODO: delete node, delete relation, remove properties, update properties, perform queries via q

}

?>
