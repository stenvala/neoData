<?php

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Single node data structure

namespace neoData;

require_once 'query.php';

class node {

  private $data = array();
  private $labels = array();
  private $nodeId = null;
  protected $defaultValues = array();

  // constructor
  public function __construct($labelOrNodeId = null, $indexKey = null, $indexValue = null) {
    if (!is_null($labelOrNodeId)) {
      if (is_null($indexKey)) {
        $cypher = 'MATCH (n) where id(n)={id} RETURN n, LABELS(n)';
        $res = query::cypher($cypher, array('id' => $labelOrNodeId));
      } else {
        $cypher = "MATCH (n:$labelOrNodeId) WHERE n.$indexKey = {indexValue} RETURN n, LABELS(n)";
        $res = query::cypher($cypher, array('indexValue' => $indexValue));
      }
      if (count($res['data']) == 0) {
        throw new \Exception('Node not found.', 204);
      } else if (count($res['data']) > 1) {
        throw new \Exception('Many nodes found with same key.', 204);
      }
      $this->initFromQueryResult($res['data'][0][0], $res['data'][0][1]);
    }
  }

  /*
   * Static
   */

  static public function create($label, $data, $indexKey = null) {
    if (!is_null($indexKey)) {
      $labelTemp = $label;
      if (is_array($labelTemp)) {
        $labelTemp = implode(':', $labelTemp);
      }
      // search first if exists, if index key is given (this could be done also via neo4j RESTapi)
      $cypher = "MATCH (n:$labelTemp) WHERE n.$indexKey = {indexValue} RETURN n";
      $res = query::cypher($cypher, array('indexValue' => $data[$indexKey]));
      if (count($res['data']) != 0) {
        throw new \Exception('Node with given index value already exists.', 204);
      }
    }
    $res = query::createNode($label, $data);
    $node = new node();
    if (!is_array($label)) {
      $label = array($label);
    }
    $node->initFromQueryResult($res['data'][0][0], $label);
    return $node;
  }

  // returns nodeId from self url
  static public function getNodeIdFromSelf($self) {
    preg_match("@(.*?)(/)([0-9]*?)($)@i", $self, $data);
    return (int) $data[3];
  }

  /*
   * Public
   */

  public function deleteProperties($data) {
    if (!is_array($data)) {
      $data = array($data);
    }
    $res = query::removeData($this->getNodeId(), $data);
    foreach ($data as $value) {
      unset($this->data[$value]);
    }
    return $res;
  }

  public function getData() {
    return $this->data;
  }

  public function getDataWithDefaultValues() {
    $data = $this->data;
    foreach ($this->defaultValues as $key => $value) {
      if (!isset($data[$key])) {
        $data[$key] = $value;
      }
    }
    return $data;
  }

  public function getLabels() {
    return $this->labels;
  }

  public function getNodeId() {
    return $this->nodeId;
  }

  public function getValue($key) {
    if (!isset($this->data[$key])) {
      if (!isset($this->defaultValues[$key])) {
        throw new Exception("Key '$key' does not exists.", 400);
      }
      return $this->defaultValues[$key];
    }
    return $this->data[$key];
  }

  public function hasLabel($label) {
    return in_array($label, $this->labels);
  }

  public function initFromQueryResult($data, $labels = null) {
    // find id, self url is like: http://localhost:7474/db/data/node/279
    if (!isset($data['self'])) {
      throw new \Exception('Remember to pass all node data.', 400);
    }
    $selfUrl = $data['self'];
    $this->nodeId = self::getNodeIdFromSelf($selfUrl);
    if (!is_null($labels)) {
      $this->labels = $labels;
    }
    $this->data = $data['data'];
  }

  public function setDefaultValues($defaultValues) {
    $this->defaultValues = $defaultValues;
  }

  public function update($data) {
    $res = query::updateData($this->getNodeId(), $data);
    foreach ($data as $key => $value) {
      $this->data[$key] = $value;
    }
    return $res;
  }

}

?>
