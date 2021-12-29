<?php

require_once '../controller/DBConnection.php';
require_once '../controller/UserConnection.php';

$db = new DBConnection();
$uc = new UserConnection($_POST, $db);

if($uc->checkAuth()){
    require_once '../controller/AccountingController.php';
    $ac = new AccountingController($_POST, $db);

    if($ac->checkParams()){
        if(@$_POST['apiType'] == 'insert') $ac->insertTransaction();
        if(@$_POST['apiType'] == 'select') $ac->selectTransaction();
        if(@$_POST['apiType'] == 'delete') $ac->deleteTransaction();
    }
}