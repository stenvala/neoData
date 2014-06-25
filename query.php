<?php

// Created: Antti Stenvall (antti@stenvall.fi)
//
// Static class for interacting with neo4j RESTapi with (so long only with) cypher
// Also some easy built-in methods for some actions are included

namespace neoData;

require_once 'config.neo4j.php';
require_once 'queryException.php';

class query {

  static public $DEBUG = false;

  // perform cypher queries
  static public function cypher($query, $params = null) {
    if (!is_array($query) && is_null($params)) {
      $query = array('query' => $query);
    } else if (!is_null($params)) {
      $query = array('query' => $query, 'params' => $params);
    }
    if (self::$DEBUG) {
      print PHP_EOL . $query['query'] . PHP_EOL;
    }
    $ch = self::initCurl(CYPHER_REST_API);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    $res = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode != 200) {
      throw new queryException($statusCode, json_decode($res, JSON_NUMERIC_CHECK), $query['query'], CYPHER_REST_API, $params);
    }
    return json_decode($res, JSON_NUMERIC_CHECK);
  }

  // Some short-cuts
  static public function deleteNode($nodeId) {
    $query = "MATCH (a) WHERE id(a) = $nodeId OPTIONAL MATCH (a)-[r]-() DELETE a,r";
    return self::cypher($query);
  }

  static public function deleteRelation($relationIdOrNodeFromId, $nodeToId = null, $label = null) {
    if (is_null($nodeToId)) {
      return self::cypher("DELETE $relationIdOrNodeFromId");
    }
    $query = 'MATCH (a)-[r';
    if (!is_null($label)) {
      $query .= ":$label";
    }
    $query .= "]->(b) WHERE id(a) = $relationIdOrNodeFromId AND id(b) = $nodeToId " .
      'WITH r DELETE r';
    return self::cypher($query);
  }

  static public function createNode($labels, $data) {
    // form query
    if (is_array($labels)) {
      $labels = implode(':', $labels);
    }
    $query = "CREATE (n:$labels {";
    $isFirst = true;
    foreach ($data as $key => $value) {
      if (!$isFirst) {
        $query .= ',';
      }
      $query .= "$key: {" . "$key}";
      $isFirst = false;
    }
    $query .= '}) RETURN n';
    $postData = array('query' => $query,
      'params' => $data);
    return self::cypher($postData);
  }

  static public function createRelation($labels, $nodeFromId, $nodeToId, $data = null) {
    if (is_array($labels)) {
      $labels = implode(':', $labels);
    }
    $query = "MATCH (a), (b) WHERE id(a) = $nodeFromId AND id(b) = $nodeToId " .
      " CREATE (a)-[r:$labels";
    if (!is_null($data) && count($data) > 0) {
      $query .= ' {';
      $isFirst = true;
      foreach ($data as $key => $value) {
        if (!$isFirst) {
          $query .= ', ';
        }
        $query .= "$key: {" . "$key}";
        $isFirst = false;
      }
      $query .= ' }';
    } else {

    }
    $query .= ']->(b)';
    return self::cypher($query, $data);
  }

  static public function removeData($id, $propertiesToDelete) {
    $query = "MATCH (n) WHERE id(n)=$id";
    $isFirst = true;
    foreach ($propertiesToDelete as $prop) {
      if (!$isFirst) {
        $query .= ',';
      }
      $query .= " REMOVE n.$prop";
    }
    $query .= ' RETURN n';
    return self::cypher($query);
  }

  static public function updateData($id, $data) {
    $query = "MATCH (n) WHERE id(n) = {$id} SET ";
    $isFirst = true;
    foreach ($data as $key => $value) {
      if (!$isFirst) {
        $query .= ', ';
      }
      $query .= "n.$key = {" . $key . "}";
      $isFirst = false;
    }
    $query .= ' RETURN n';
    $postData = array('query' => $query,
      'params' => $data);
    return self::cypher($postData);
  }

  // Private for CURL initialization
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
