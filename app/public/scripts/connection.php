<?php
require_once dirname(__FILE__) . '/connectionConfig.php';

class Connection
{
  public $dbh;
  public $error;

  public function __construct()
  {
    try {
      $this->dbh = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
    }
  }
}
