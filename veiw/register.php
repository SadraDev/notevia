<?php

require_once '../controller/DBConnection.php';
require_once '../controller/UserConnection.php';

$db = new DBConnection();
$uc = new UserConnection($_POST, $db);

$uc->checkRegisterParams();

if ($uc->checkExist()) {
    exit(
    json_encode([
        'result' => false,
        'error' => 'already User exist!'
    ]));
} else {
    $uc->storeUser();
}