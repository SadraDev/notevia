<?php

require_once '../controller/DBConnection.php';
require_once '../controller/UserConnection.php';

$db = new DBConnection();
$uc = new UserConnection($_POST, $db);

if($uc->checkActivationParams()) $uc->checkActivationCode();
