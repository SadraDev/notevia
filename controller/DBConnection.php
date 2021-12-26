<?php

require_once 'config.php';
require_once '../model/ModelTables.php';

class DBConnection
{
    public mysqli $db;
    public ModelTables $modelTables ;

    public function __construct()
    {
        $this->db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $this->modelTables = new ModelTables();
    }

    public function getTableNames(){

    }


}