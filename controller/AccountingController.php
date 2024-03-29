<?php

class AccountingController
{
    private DBConnection $db;
    private array $post;
    private mysqli $conn;
    private array $response;

    public function __construct($post, $db)
    {
        $this->db = $db;
        $this->conn = $this->db->db;
        $this->post = $post;
        $this->response = [];
    }

    public function checkParams(){
        $apiType = @$this->post['apiType'];
        if ($apiType == null) {
            exit(json_encode([
                'result' => false,
                'error' => 'apiType required',
                'action' => 'NO_API_TYPE_IS_SENT'
            ]));
        }

        switch ($apiType) {
            case 'insert' : {
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');
                if (!isset($this->post['catId'])) array_push($this->response, 'catId required');
                if (!isset($this->post['transactionValue'])) array_push($this->response, 'transactionValue required');
                if (!isset($this->post['transactionType'])) array_push($this->response, 'transactionType required');

                if(count($this->response) > 0){
                    exit(json_encode(
                        [
                            'result' => false,
                            'error' => $this->response,
                            'action' => 'INSERTION_ERROR'
                        ]
                    ));
                }
                break;
            }

            case 'select' : {
                if (!isset($this->post['catId'])) array_push($this->response, 'catId required');
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');

                if(count($this->response) > 0){
                    exit(json_encode(
                        [
                            'result' => false,
                            'error' => $this->response,
                            'action' => 'SELECTION_ERROR'
                        ]
                    ));
                }
                break;
            }

            case 'delete' : {
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');
                if (!isset($this->post['id'])) array_push($this->response, 'id required');

                if(count($this->response) > 0){
                    exit(json_encode(
                        [
                            'result' => false,
                            'error' => $this->response,
                            'action' => 'DELETION_ERROR'
                        ]
                    ));
                }
                break;
            }
        }
        return true;
    }

    public function insertTransaction(){
        $file = @$_FILES['file'];
        $fileName = null;
        if(!empty($file)){
            if($file['size'] > 2000000){
                exit(json_encode(
                    [
                        'result' => false,
                        'error' => 'file size too big',
                        'action' => 'FILE_SIZE_OVER_LIMIT'
                    ]
                ));
            }
            $now = strtotime(date("Y-m-d H:i:s"));
            $fileName = $this->post['userId'] . $now . ".jpeg";
            move_uploaded_file($file['tmp_name'], '../uploads/' . $fileName);
        }
        $stmt = 'INSERT INTO `tbl_accounting` (`user_id`, `cat_id`, `transaction_value`, `transaction_type`, `description`, `title`, `file`) values (?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('iisisss', $this->post['userId'], $this->post['catId'], $this->post['transactionValue'], $this->post['transactionType'], $this->post['description'], $this->post['title'], $fileName);
        $result = $stmt->execute();
        if ($result) {
            echo json_encode(
                [
                    'result' => true,
                    'msg' => 'transaction inserted',
                    'action' => 'INSERTED'
                ]
            );
        } else {
            echo json_encode(
                [
                    'result' => false,
                    'msg' => 'transaction didnt inserted',
                    'action' => 'INSERT_FAILED'
                ]
            );
        }
    }

    public function selectTransaction()
    {
        $stmt = "SELECT * FROM `tbl_accounting` where `user_id` = ? and `cat_id` = ? and `deleted` = false";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('ii', $this->post['userId'], $this->post['catId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = array();

        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        echo json_encode(
            [
                'result' => true,
                'transactions' => $transactions,
                'action' => 'SELECTED'
            ]
        );
    }

    public function deleteTransaction()
    {
        $stmt = "UPDATE `tbl_accounting` set `deleted` = true where `user_id` = ? and `id` = ?";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('ii', $this->post['userId'], $this->post['id']);
        if ($stmt->execute()){
            echo json_encode([
                'result' => true,
                'msg' => 'deleted',
                'action' => 'DELETED'
            ]);
        }
    }
}