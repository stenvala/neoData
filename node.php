<?php

// Single node data structure

namespace neoData;

require_once 'query.php';

class node {

  private $data;
  private $nodeId = null;
  protected $defaultValues = array();

  // constructor
  public function __construct($labelOrNodeId = null, $indexKey = null, $indexValue = null) {
    if (!is_null($labelOrNodeId)) {
      if (is_null($indexKey)) {
        $cypher = "MATCH (n) where id(n)={id} RETURN n";
        $res = query::cypher($cypher, array('id' => $labelOrNodeId));
      } else {
        $cypher = "MATCH (n:$labelOrNodeId) WHERE n.$indexKey = {indexValue} RETURN n";
        $res = query::cypher($cypher, array('indexValue' => $indexValue));
      }
      if (count($res['data']) == 0) {
        throw new \Exception('Node not found.', 204);
      }
      $this->initFromQueryResult($res['data'][0][0]);
    }
  }

  /*
   * Static
   */

  static public function create($label, $data, $indexKey = null) {
    if (!is_null($indexKey)) {
      $cypher = "MATCH (n:$label) WHERE n.$indexKey = {indexValue} RETURN n";
      $res = query::cypher($cypher, array('indexValue' => $data[$indexKey]));
      if (count($res['data']) != 0) {
        throw new \Exception('Node with given index value already exists.', 204);
      }
    }
    $res = query::createNode($label, $data);
    $node = new node();
    $node->initFromQueryResult($res['data'][0][0]);
    return $node;
  }

  static public function getNodeIdFromSelf($self) {
    preg_match("@(.*?)(/)([0-9]*?)($)@i", $self, $data);
    return (int) $data[3];
  }

  /*
   * Public
   */

  public function getNodeId() {
    return $this->nodeId;
  }

  public function getValue($key) {
    return $this->data[$key];
  }

  public function update($data) {
    if (!is_null($nodeId = $this->getNodeId())) {
      $res = query::updateNodeData($nodeId, $data);
      foreach ($data as $key => $value) {
        $this->data[$key] = $value;
      }
      return $res;
    }
    throw new Exception('Node id not known. Cannot update.', 400);
  }

  public function initFromQueryResult($nodeData) {
    // find id, self url is like: http://localhost:7474/db/data/node/279
    $selfUrl = $nodeData['self'];
    $this->nodeId = self::getNodeIdFromSelf($selfUrl);
    $this->data = $nodeData['data'];
    foreach ($this->defaultValues as $key => $value) {
      if (!isset($this->data[$key])) {
        $this->data[$key] = $value;
      }
    }
  }

}

?>
