<?php

// Single node data structure

namespace neoData;

require_once 'config.neo4j.php';
require_once 'query.php';

class node {

  private $data;
  private $nodeId;
  protected $defaultValues = array();

  // constructor
  public function __construct($labelOrNodeId, $indexKey = null, $indexValue = null) {
    if (is_null($indexKey)) {
      $cypher = "MATCH (n) where id(n)={id} RETURN n";
      $res = query::cypher(CYPHER_API, array('query' => $cypher,
          'params' => array('id' => $labelOrNodeId)
      ));
    } else {
      $cypher = "MATCH (n:$labelOrNodeId) WHERE n.$indexKey = {indexValue} RETURN n";
      $res = query::cypher(CYPHER_API, array('query' => $cypher,
          'params' => array('indexValue' => $indexValue)
      ));
    }
    if (count($res['data']) == 0) {
      throw new \Exception('Node not found.', 204);
    }
    // find id, self url is like: http://localhost:7474/db/data/node/279
    $selfUrl = $res['data'][0][0]['self'];
    preg_match("@(.*?)(/)([0-9]*?)($)@i", $selfUrl, $data);
    $this->nodeId = (int) $data[3];
    $this->data = $res['data'][0][0]['data'];
    foreach ($this->defaultValues as $key => $value) {
      if (!isset($this->data[$key])) {
        $this->data[$key] = $value;
      }
    }
  }

  /*
   * Static
   */

  static protected function create($label, $data) {
    $res = query::createNode(CYPHER_API, $label, $data);
    return $res;
  }

  /*
   * Protected
   */

  protected function getNodeId() {
    return $this->nodeId;
  }

  protected function getValue($key) {
    return $this->data[$key];
  }

  protected function update($data){
    $res = query::updateNodeData(CYPHER_API, $this->getNodeId(), $data);
    foreach ($data as $key => $value){
      $this->data[$key] = $value;
    }
  }

}

?>
