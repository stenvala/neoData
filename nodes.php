<?php

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Some methods to construct sets of nodes

namespace neoData;

require_once 'query.php';
require_once 'node.php';

class nodes {

  const BOTH = 0;
  const FROM = 1;
  const TO = 2;

  // find other node via relation that is at depth 1
  static public function constructViaRelation(node $start, $type = null, $direction = self::BOTH, $nodeType = null, $indexKey = null, $indexValue = null, $additionalWhere = null) {
    $types = array($type);
    $directions = array($direction);
    $nodeTypes = array($nodeType);
    $indexKeys = array($indexKey);
    $indexValues = array($indexValue);
    $matrix = self::constructViaTraverse($start, 1, $types, $directions, $nodeTypes, $indexKeys, $indexValues, $additionalWhere, null);
    $data = array();
    foreach ($matrix as $val) {
      array_push($data, $val[0]);
    }
    return $data;
  }

  // construct by finding specific paths between $start and $end
  static public function constructViaFindingPath(node $start, node $end, $depth, $types = null, $directions = self::BOTH, $nodeTypes = null, $indexKeys = null, $indexValues = null, $additionalWhere = null, $with = null, $additionalReturn = null) {
    return self::constructViaTraverse($start, $depth, $types, $directions, $nodeTypes, $indexKeys, $indexValues, $additionalWhere, $with, $additionalReturn, $end);
  }

  // construct lists of nodes via traversing from start node to given dept, all the optional parameters can be arrays of size $depth
  static public function constructViaTraverse(node $start, $depth, $types = null, $directions = self::BOTH, $nodeTypes = null, $indexKeys = null, $indexValues = null, $additionalWhere = null, $with = null, $additionalReturn = null, node $end = null) {
    $startId = $start->getNodeId();
    if (is_null($startId)) {
      throw new \Exception('Start node does not exist.', 204);
    }
    if (!is_null($end)) {
      $endId = $end->getNodeId();
      if (is_null($endId)) {
        throw new \Exception('End node does not exist.', 204);
      }
    }
    $types = !is_array($types) ? array_fill(0, $depth, $types) : $types;
    $directions = !is_array($directions) ? array_fill(0, $depth, $directions) : $directions;
    $nodeTypes = !is_array($nodeTypes) ? array_fill(0, $depth, $nodeTypes) : $nodeTypes;
    $indexKeys = !is_array($indexKeys) ? array_fill(0, $depth, $indexKeys) : $indexKeys;
    $indexValues = !is_array($indexValues) ? array_fill(0, $depth, $indexValues) : $indexValues;
    $cypher = 'MATCH (a)';
    foreach ($types as $key => $value) {
      if ($directions[$key] == self::TO) {
        $cypher .= '<';
      }
      $cypher .= '-[r' . $key;
      if (!is_null($types[$key])) {
        $cypher .= ':' . $types[$key];
      }
      $cypher .= ']-';
      if ($directions[$key] == self::FROM) {
        $cypher .= '>';
      }
      $cypher .= '(b' . $key;
      if (count($nodeTypes) > $key && !is_null($nodeTypes[$key])) {
        $cypher .= ':' . $nodeTypes[$key];
      }
      $cypher .= ')';
    }
    $cypher .= " WHERE id(a)=$startId";
    $params = array();
    $returnStatement = ' RETURN';
    foreach ($types as $key => $value) {
      if (!is_null($indexKeys[$key])) {
        $searchParam = 'b' . $key . $indexKeys[$key];
        $params[$searchParam] = $indexValues[$key];
        $cypher .= " and b$key.{$indexKeys[$key]} = {" . $searchParam . "}";
      }
      if (!(!is_null($end) && ($key + 1) == count($types))) {
        if ($key > 0) {
          $returnStatement .= ', ';
        }
        $returnStatement .= " b$key, LABELS(b$key)";
      }
    }
    if (!is_null($end)) {
      $cypher .= ' and id(b' . (count($types) - 1) . ')=' . $endId;
    }
    if (!is_null($additionalWhere)) {
      $cypher .= " and $additionalWhere";
    }
    if (!is_null($with)){
      $cypher .= " WITH $with";

    }
    if (count($params) == 0) {
      $params = null;
    }
    $cypher .= $returnStatement;
    if (!is_null($additionalReturn)){
      $cypher .= ", $additionalReturn";
    }
    print $cypher . PHP_EOL;
    $data = query::cypher($cypher, $params);
    $matrix = array();
    if (isset($data['data'])) {
      foreach ($data['data'] as $key => $value) {
        $traverse = array();
        for ($ind = 0; $ind < count($value); $ind = $ind + 2) {
          $node = new node();
          $node->initFromQueryResult($value[$ind], $value[$ind + 1]);
          array_push($traverse, $node);
        }
        array_push($matrix, $traverse);
      }
    }
    return $matrix;
  }

}

?>
