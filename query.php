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

  const CLAUSE_CREATE = 'CREATE';
  const CLAUSE_DELETE = 'DELETE';
  const CLAUSE_MATCH = 'MATCH';
  const CLAUSE_OPTIONAL_MATCH = 'OPTIONAL MATCH';
  const CLAUSE_REMOVE = 'REMOVE';
  const CLAUSE_RETURN = 'RETURN';
  const CLAUSE_SET = 'SET';
  const CLAUSE_WHERE = 'WHERE';
  const CLAUSE_WITH = 'WITH';

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

  // perform any queries by getting or posting data to given url
  static public function q($url, $post = null) {
    $ch = self::initCurl($url);
    if (!is_null($post)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    $res = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode != 200) {
      throw new queryException($statusCode, json_decode($res, JSON_NUMERIC_CHECK), $post, $url);
    }
    return json_decode($res, JSON_NUMERIC_CHECK);
  }

  // Few simple boilerplate query functions which include some easy cypher

  static public function createNode($labels, $data) {
    // form query
    if (is_array($labels)) {
      $labels = implode(':', $labels);
    }
    $query = self::CLAUSE_CREATE . " (n:$labels {" .
      self::appendCypher(self::CLAUSE_CREATE, $data) .
      '}) ' . self::CLAUSE_RETURN . ' n';
    $postData = array('query' => $query,
      'params' => $data);
    return self::cypher($postData);
  }

  static public function createRelation($labels, $nodeFromId, $nodeToId, $data = null) {
    if (is_array($labels)) {
      $labels = implode(':', $labels);
    }
    $query = self::CLAUSE_MATCH . ' (a), (b) ' .
      self::CLAUSE_WHERE . " id(a) = $nodeFromId AND id(b) = $nodeToId " .
      self::CLAUSE_CREATE . " (a)-[n:$labels {" .
      self::appendCypher(self::CLAUSE_CREATE, $data) .
      ' }]->(b)';
    return self::cypher($query, $data);
  }

  static public function deleteNode($nodeId) {
    $query = self::CLAUSE_MATCH . ' (a) ' .
      self::CLAUSE_WHERE . " id(a) = $nodeId " .
      self::CLAUSE_OPTIONAL_MATCH . ' (a)-[r]-() ' .
      self::CLAUSE_DELETE . ' a, r';
    return self::cypher($query);
  }

  static public function deleteRelation($relationIdOrNodeFromId, $nodeToId = null, $label = null) {
    if (is_null($nodeToId)) {
      return self::cypher("DELETE $relationIdOrNodeFromId");
    }
    $query = self::CLAUSE_MATCH . ' (a)-[r';
    $query .= is_null($label) ? '' : ":$label";
    $query .= ']->(b) ' .
      self::CLAUSE_WHERE . " id(a) = $relationIdOrNodeFromId AND id(b) = $nodeToId " .
      self::CLAUSE_WITH . ' r ' .
      self::CLAUSE_DELETE . ' r';
    return self::cypher($query);
  }

  static public function getNode($label, $props) {
    $query = self::CLAUSE_MATCH . " (n:$label) " .
      self::CLAUSE_WHERE . ' ' .
      self::appendCypher(self::CLAUSE_WHERE, $props) .
      self::CLAUSE_RETURN . ' n';
    return self::cypher($query, $props);
  }

  static public function getNodeData($labelOrNode, $props = null) {
    if (!is_null($props)) {
      $labelOrNode = self::getNode($labelOrNode, $props);
    }
    if (!isset($labelOrNode['data'][0][0]['data'])) {
      throw new Exception("Not valid note data", 400);
    }
    return $labelOrNode['data'][0][0]['data'];
  }

  static public function getNodeIdFromData($node) {
    return self::getNodeIdFromSelf($node['data'][0][0]['self']);
  }

  // returns nodeId from self url
  static public function getNodeIdFromSelf($self) {
    preg_match("@(.*?)(/)([0-9]*?)($)@i", $self, $data);
    $val = (int) $data[3];
    if (gettype($val) != 'integer' || $val < 0) {
      throw new \Exception('Could not parse id from link', 400);
    }

    return $val;
  }

  static public function removeData($id, $propertiesToDelete) {
    $query = self::CLAUSE_MATCH . ' (n) ' .
      self::CLAUSE_WHERE . " id(n)=$id " .
      self::appendCypher(self::CLAUSE_REMOVE, $propertiesToDelete) .
      self::CLAUSE_RETURN . ' n';
    return self::cypher($query);
  }

  static public function updateData($id, $data) {
    $query = self::CLAUSE_MATCH . ' (n) ' .
      self::CLAUSE_WHERE . " id(n)=$id " .
      self::CLAUSE_SET .
      self::appendCypher(self::CLAUSE_SET, $data) .
      self::CLAUSE_RETURN . ' n';
    $postData = array('query' => $query,
      'params' => $data);
    return self::cypher($postData);
  }

  // Helpers

  static public function appendCypher($clause, $data) {
    $app = '';
    $isFirst = true;
    if (!is_array($data)) {
      return;
    }
    foreach ($data as $key => $value) {
      switch ($clause) {
        case self::CLAUSE_CREATE:
          if (!$isFirst) {
            $app .= ',';
          }
          $app .= "$key: {" . "$key}";
          break;
        case self::CLAUSE_REMOVE:
          if (!$isFirst) {
            $app .= ' ,';
          }
          $app .= " REMOVE n.$value";
          break;
        case self::CLAUSE_SET:
          if (!$isFirst) {
            $app .= ' ,';
          }
          $app .= " n.$key = {" . "$key}";
          break;
        case self::CLAUSE_WHERE:
          if (!$isFirst) {
            $app .= ' and';
          }
          $app .= " n.$key = {" . "$key}";
          break;
        default:
          throw new \Exception('Unknown clause', 400);
      }
      $isFirst = false;
    }
    return $app . ' ';
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
