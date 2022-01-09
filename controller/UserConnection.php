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
        $name = @$this->data['name'];
        $email = @$this->data['email'];
        $password = @$this->data['password'];

        if ($name == null or $name == '') array_push($this->response, 'name required!');
        if ($email == null or $email == '') array_push($this->response, 'email required!');
        if ($password == null or $password == '') array_push($this->response, 'password required!');

        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response,
                    'action' => 'REGISTER_FAILED'
                ]
            )
            );
        }
    }

    public function checkExist()
    {
        $stmt = "SELECT * FROM `tbl_user` WHERE `email` = ?";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("s", $this->data['email']);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0){
            $row = $result->fetch_assoc();

            switch ($row['status']){
                case "NEW_USER":{
                    $this->updateOrInsertRandomCode($row['id'], $row['email']);
                    exit(json_encode([
                        'result' => true,
                        'msg' => 'Please verify your account',
                        'action' => "NEW_USER"
                    ]));
                }
                case "ACTIVE_USER" :{
                    exit(json_encode(
                        [
                            'result' => false,
                            'error' => 'User already exist!',
                            'action' => 'ACTIVE_USER'
                        ]
                    ));
                }
            }
        }
    }

    public function storeUser()
    {
        $password = hash('sha256', $this->data['password']);
        $stmt = "INSERT INTO `tbl_user` (`name`, `email`, `hash_password`, `status`) values (?, ?, ?, 'NEW_USER')";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("sss", $this->data['name'], $this->data['email'], $password);
        $stmt->execute();

        // mb_send_mail() in php 8, 8 < ~
        $this->updateOrInsertRandomCode($stmt->insert_id, $this->data['email']);

        exit(json_encode([
            'result' => true,
            'msg' => 'Please verify your account',
            'action' => "NEW_USER"
        ]));
    }

    public function checkLoginParams()
    {
        $email = @$this->data['email'];
        $password = @$this->data['password'];

        if ($email == null or $email == '') {
            array_push($this->response, 'email required!');
        }

        if ($password == null or $password == '') {
            array_push($this->response, 'password required!');
        }

        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response,
                    'action' => 'LOGIN_FAILED'
                ]
            )
            );
        }
    }

    public function checkLogin()
    {
        $email = @$this->data['email'];
        $pass = @$this->data['password'];

        $stmt = 'SELECT * FROM `tbl_user` WHERE email = ?';
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('s', $email);
        if($stmt->execute()){
            $result = $stmt->get_result();

            if ($result->num_rows <= 0) {
                exit(json_encode(
                    [
                        'result' => false,
                        'error' => 'user not exist',
                        'action' => 'NO_USER_FOUND'
                    ]
                ));
            } else {
                $row = $result->fetch_assoc();

                if($row['status'] == 'NEW_USER'){
                    exit(json_encode([
                        'result' => false,
                        'error' => 'Please activate your account',
                        'action' => 'NEW_USER'
                    ]));
                }

                if ($row['hash_password'] == hash('sha256', $pass)) {
                    exit(json_encode(
                        [
                            'result' => true,
                            'msg' => 'logged in',
                            'action' => 'ACTIVE_USER'
                        ]
                    ));
                } else {
                    exit(json_encode(
                        [
                            'result' => false,
                            'msg' => 'wrong password',
                            'active' => 'WRONG_PASSWORD'
                        ]
                    ));
                }
            }
        }
    }

    protected function getIdByUser($email)
    {
        if ($email == null or $email == '') array_push($this->response, 'email required!');
        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response,
                    'action' => 'USER_NOT_FOUND'
                ]
            )
            );
        }

        $stmt = 'SELECT * FROM `tbl_user` WHERE `email` = ?';
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        return false;
    }

    public function forgotPassword()
    {
        $email = $this->data['email'];
        if ($email == null or $email == '') array_push($this->response, 'email required!');
        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response,
                    'action' => 'EMAIL_FIELD_REQUIRED'
                ]
            )
            );
        }
        $userId = $this->getIdByUser($email);
        if ($userId != false) {
            $this->updateOrInsertRandomCode($userId, $email);
        } else {
            exit(json_encode(
                [
                    'result' => false,
                    'error' => 'id not found',
                    'action' => 'USER_NOT_FOUND'
                ]
            ));
        }
    }

    public function resetPassword()
    {
        $email = $this->data['email'];
        $new_password = $this->data['new_password'];
        $code = $this->data['code'];
        if ($email == null or $email == '') array_push($this->response, 'email required!');
        if ($new_password == null or $email == '') array_push($this->response, 'new_password required!');
        if ($code == null or $email == '') array_push($this->response, 'code required!');
        if (count($this->response) > 0) {
            exit(
            json_encode(
                [
                    'result' => false,
                    'error' => $this->response,
                    'action' => 'RESET_PASSWORD_FAILED'
                ]
            )
            );
        }

        $userId = $this->getIdByUser($email);
        if ($userId != false){
            $stmt = "SELECT * FROM tbl_auth WHERE user_id = ? and `code` = ?";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("ii", $userId, $code);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                $password = hash('sha256', $new_password);
                $stmt = "UPDATE `tbl_user` SET `hash_password` = ? WHERE `id` = ?";
                $stmt = $this->conn->prepare($stmt);
                $stmt->bind_param('ss',$password ,$userId);
                $stmt->execute();

                exit(json_encode(
                    [
                        'result' => true,
                        'msg' => 'password updated',
                        'action' => 'PASSWORD_UPDATE_SUCCESSFUL'
                    ]
                ));
            }
        } else {
            exit(json_encode(
                [
                    'result' => false,
                    'error' => 'id not found',
                    'action' => 'USER_NOT_FOUND'
                ]
            ));
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
            $row = $result->fetch_assoc();
            if($row['status'] == 'ACTIVE_USER') return $result->num_rows > 0;
            if ($row['status'] == 'NEW_USER') {
                exit(json_encode(
                    [
                        'result' => false,
                        'msg' => 'user not active',
                        'action' => 'USER_NOT_ACTIVE'
                    ]
                ));
            }
        } else {
            exit(json_encode(
                [
                    'result' => false,
                    'msg' => 'auth error',
                    'action' => 'USER_NOT_FOUND'
                ]
            ));
        }
    }

    private function sendEmailTo($to, int $randomCode)
    {
        $subject = "Verification code";

        $message = "
                    <html>
                    <head>
                    <title>Welcome to NoteVia</title>
                    </head>
                    <body>
                    <p>your verification code is : $randomCode</p>
                    </body>
                    </html>
                    ";

// It is mandatory to set the content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers. From is required, rest other headers are optional
        $headers .= 'From: <info@phloxco.ir>' . "\r\n";
        $headers .= 'Cc: info@phloxco.ir' . "\r\n";

        mb_send_mail($to,$subject,$message,$headers);
    }

    private function updateOrInsertRandomCode($userId, $email)
    {
        $rand = rand(10000, 99999);
        $now = date("Y-m-d H:i:s");

        $stmt = "SELECT * FROM tbl_auth WHERE user_id = ? ";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            $stmt = "UPDATE `tbl_auth` SET `code` = ?, `date` = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("isi", $rand ,$now ,$userId );

        } else {
            $stmt = "INSERT INTO `tbl_auth` (user_id , code, date) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("iis", $userId, $rand, $now);

        }
        $stmt->execute();

        $this->sendEmailTo($email, $rand);
    }

    public function checkActivationParams() : bool
    {
        if (!isset($this->data['email'])) array_push($this->response, 'email required!');
        if (!isset($this->data['code'])) array_push($this->response, 'code required!');

        if (count($this->response) > 0) {
            exit(
                json_encode(
                    [
                        'result' => false,
                        'error' => $this->response,
                        'action' => 'ACTIVATION_FAILED'
                    ]
                )
            );
        }
        return true;
    }

    public function checkActivationCode()
    {
        $userId = $this->getIdByUser($this->data['email']);
        if ($userId == false) {
            exit(json_encode(
                [
                    'result' => false,
                    'error' => 'user not found',
                    'action' => 'USER_NOT_FOUND'
                ]
            ));
        }
        $stmt = "SELECT * FROM tbl_auth WHERE user_id = ? ";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows <= 0) {
            exit(
                json_encode(
                    [
                        'result' => false,
                        'error' => 'user not found',
                        'action' => 'USER_NOT_FOUND'
                    ]
                )
            );
        }
        $row = $result->fetch_assoc();
        if ($row['code'] == $this->data['code']) {
            $now = strtotime(date("Y-m-d H:i:s"));
            if (strtotime($row['date']) + 120 > $now){
                $stmt = "UPDATE `tbl_user` SET `status` = 'ACTIVE_USER' WHERE id = ?";
                $stmt = $this->conn->prepare($stmt);
                $stmt->bind_param("i",$userId);
                if($stmt->execute()){
                    exit(
                    json_encode(
                        [
                            'result' => true,
                            'msg' => 'account activated',
                            'action' => 'USER_ACTIVATED',
                        ]
                    )
                    );
                }
            }else{
                exit(
                json_encode(
                    [
                        'result' => false,
                        'msg' => 'code is expired!',
                        'action' => 'CODE_EXPIRED'
                    ]
                )
                );
            }
        } else {
            exit(
                json_encode(
                    [
                        'result' => false,
                        'error' => 'wrong code',
                        'action' => 'WRONG_CODE'
                    ]
                )
            );
        }
    }
}