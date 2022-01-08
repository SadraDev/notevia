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

    public function checkExist()
    {

        $stmt = "SELECT * FROM `tbl_user` WHERE `name` = ? or `email` = ?";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("ss", $this->data['name'], $this->data['email']);
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
                    exit(json_encode(['result' => false, 'error' => 'User already exist!']));
                }
            }
        }

        /// else user not exist and result is true
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

                if($row['status'] == 'NEW_USER'){
                    exit(json_encode(['result' => false, 'error' => 'Please active your account']));
                }

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
        $rand = rand(100000, 999999);

        $stmt = "SELECT * FROM tbl_auth WHERE user_id = ? ";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            // update

            $stmt = "UPDATE `tbl_auth` SET code = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("ii", $rand ,$userId );

        } else {

            $stmt = "INSERT INTO `tbl_auth` (user_id , code) VALUES (?, ?)";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("ii", $userId, $rand);

        }
        $stmt->execute();

        $this->sendEmailTo($email, $rand);
    }

    public function checkActivationParams() : bool
    {
        if (!isset($this->data['userId'])) array_push($this->response, 'userId required!');
        if (!isset($this->data['code'])) array_push($this->response, 'code required!');

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
        return true;
    }

    public function checkActivationCode(){

        $stmt = "SELECT * FROM tbl_auth WHERE user_id = ? ";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param("i", $this->data['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $code = $result->fetch_assoc();

        if ($code['code'] == $this->data['code']) {
            $stmt = "UPDATE `tbl_user` SET `status` = 'ACTIVE_USER' WHERE id = ?";
            $stmt = $this->conn->prepare($stmt);
            $stmt->bind_param("i",$this->data['userId']);
            if($stmt->execute()){
                exit(
                    json_encode(
                        [
                            'result' => true,
                            'msg' => 'account activated'
                        ]
                    )
                );
            }
        } else {
            exit(
                json_encode(
                    [
                        'result' => false,
                        'error' => 'wrong code'
                    ]
                )
            );
        }
    }
}