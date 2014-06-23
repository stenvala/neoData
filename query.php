<?php

namespace neoData;

class query {

  static public function cypher($url, $query) {
    if (!is_array($query)) {
      $query = array('query' => $query);
    }
    $ch = self::initCurl($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    $res = curl_exec($ch);
    // curl_getinfo($ch, CURLINFO_HTTP_CODE); <- returns the http code
    curl_close($ch);
    return json_decode($res, JSON_NUMERIC_CHECK);
  }

  static public function createNode($url, $labels, $data) {
    // form query
    if (is_array($labels)) {
      $labels = implode(':', $labels);
    }
    $query = 'CREATE (n:' . $labels . ' {';
    $isFirst = true;
    foreach ($data as $key => $value) {
      if (!$isFirst) {
        $query .= ',';
      }
      $query .= $key . ' : {' . $key . '} ';
      $isFirst = false;
    }
    $query .= '}) RETURN n';
    $postData = array('query' => $query,
      'params' => $data);
    return self::cypher($url, $postData);
  }

  static public function updateNodeData($url, $nodeId, $data) {
    $query = "MATCH (n) WHERE id(n) = {id} SET ";
    $isFirst = true;
    foreach ($data as $key => $value) {
      if (!$isFirst) {
        $query .= ', ';
      }
      $query .= "n.$key = {" . $key . "}";
    }
    $query .= "RETURN n";

    $postData = array('query' => $query,
      'params' => array_merge($data, array('id' => $nodeId)));
    return self::cypher($url, $postData);
  }

  static private function initCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
      'Accept: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return $ch;
  }

}

?>