<?php

require_once '../model/ModelTableUser.php';

class UserConnection
{
    private array $data;
    private DBConnection $db;
    private mysqli $conn;
    public array $response;

    public function __construct($data, $db)
    {
        $this->db = $db;
        $this->conn = $this->db->db;
        $this->data = $data;
        $this->response = [];
    }

    public function checkRegisterParams()
    {
        if (!isset($this->data['name'])) {
            array_push($this->response, 'name required!');
        }
        if (!isset($this->data['email'])) {
            array_push($this->response, 'email required!');
        }
        if (!isset($this->data['password'])) {
            array_push($this->response, 'password required!');
        }

        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response
                ]
            )
            );
        }
    }

    public function checkExist() : bool{

//        $tbl_name = $tbl::TBL_USER;
//        $columns = $tbl->modelTableUser;
//        $email = $columns::COL_NAME;
        $stmt = "SELECT * FROM `tbl_user` WHERE `name` = ? or `email` = ?";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("ss", $this->data['name'], $this->data['email']);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->num_rows > 0;

    }

    public function storeUser()
    {
        $password = hash('sha256', $this->data['password']);
        $stmt = "INSERT INTO `tbl_user` (`name`, `email`, `hash_password`, `status`) values (?, ?, ?, 'NEW_USER')";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("sss", $this->data['name'], $this->data['email'], $password);
        $stmt->execute();

        echo json_encode([
            'result' => true,
            'msg' => 'User inserted'
        ]);
    }

    public function checkLoginParams()
    {
        $userName = @$this->data['name'];
        $pass = @$this->data['password'];

        if (!isset($userName)) {
            array_push($this->response, 'name required!');
        }

        if (!isset($pass)) {
            array_push($this->response, 'password required!');
        }

        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response
                ]
            )
            );
        }
    }

    public function checkLogin()
    {
        $userName = @$this->data['name'];
        $pass = @$this->data['password'];

        $stmt = 'SELECT * FROM `tbl_user` WHERE name = ? or email = ?';
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('ss', $userName, $userName);
        if($stmt->execute()){
            $result = $stmt->get_result();

            if ($result->num_rows <= 0) {
                exit(json_encode(
                    [
                        'result' => false,
                        'error' => 'user not exist'
                    ]
                ));
            } else {
                $row = $result->fetch_assoc();
                if ($row['hash_password'] == hash('sha256', $pass)) {
                    exit(json_encode(
                        [
                            'result' => true,
                            'msg' => 'ok'
                        ]
                    ));
                } else {
                    exit(json_encode(
                        [
                            'result' => false,
                            'msg' => 'wrong password'
                        ]
                    ));
                }
            }
        }
    }
    public function checkAuth()
    {
        $userId = @$_POST['userId'];
        $password = @$_POST['password'];

        if(isset($userId) and isset($password)) {
            $password = hash('sha256', $password);
            $stmt = 'SELECT * FROM `tbl_user` WHERE `id` = ? and `hash_password` = ?';
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param('is', $userId, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0;
        } else {
            exit(json_encode(
                [
                    'result' => false,
                    'msg' => 'auth error'
                ]
            ));
        }
    }
}