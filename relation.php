<?php

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Some methods related to relations

namespace neoData;

require_once 'config.neo4j.php';
require_once 'query.php';
require_once 'node.php';

class relation {

  static public function create($labels, node $nodeFrom, node $nodeTo, $data = null) {
    $idFrom = $nodeFrom->getNodeId();
    $idTo = $nodeTo->getNodeId();
    if (is_null($idTo) || is_null($idFrom)) {
      throw new \Exception('At least one node does not have nodeId. Cannot create relationship.', 400);
    }
    $res = query::createRelation($labels, $idFrom, $idTo, $data);
    return $res;
  }

}

?>
