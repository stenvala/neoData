<?php

namespace neoData;

class queryException extends \Exception {

  private $fullName;
  private $params;
  private $query;
  private $stackTrace;
  private $type;
  private $url;

  public function __construct($http_code, $res, $query, $url, $params = null) {
    parent::__construct($res['message'], $http_code);
    $this->type = $res['exception'];
    $this->fullName = $res['fullname'];
    $this->stackTrace = $res['stacktrace'];
    $this->params = $params;
    $this->query = $query;
    $this->url = $url;
  }

  public function getFullName() {
    return $this->fullName;
  }

  public function getParams() {
    return $this->params;
  }

  public function getQuery() {
    return $this->query;
  }

  public function getStackTrace() {
    return $this->stackTrace();
  }

  public function getType() {
    return $this->type;
  }

  public function getUrl(){
    return $this->url;
  }

}

?>
