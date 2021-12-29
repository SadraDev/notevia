<?php

require_once '../controller/DBConnection.php';
require_once '../controller/UserConnection.php';


$db = new DBConnection();
$uc = new UserConnection($_POST, $db);

if($uc->checkAuth() === true) {
    require_once '../controller/CategoryController.php';
    $cc = new CategoryController($_POST, $db);

    if($cc->checkParams() === true){
        if(@$_POST['apiType'] == 'insert') $cc->insertCategory();
        if(@$_POST['apiType'] == 'select') $cc->selectCategory();
        if(@$_POST['apiType'] == 'delete') $cc->deleteCategory();
    }
    
}
