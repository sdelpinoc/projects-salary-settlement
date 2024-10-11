<?php
require_once dirname(__FILE__) . '/db/Connection.php';

$db = (new Connection())->getConnection();