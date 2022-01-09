<?php

class CategoryController
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

    public function checkParams()
    {
        $apiType = @$this->post['apiType'];
        if (!isset($apiType)) {
            exit(json_encode([
                'result' => false,
                'error' => 'api type required',
                'action' => 'NO_API_TYPE_IS_SENT'
            ]));
        }

        switch ($apiType) {
            case 'insert':
            {
                if (!isset($this->post['name'])) array_push($this->response, 'name required');
                if (!isset($this->post['parentId'])) array_push($this->response, 'parentId required');
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');

                if (count($this->response) > 0) {
                    exit(json_encode([
                        'result' => false,
                        'error' => $this->response,
                        'action' => 'INSERTION_ERROR'
                    ]));
                }
                break;
            }
            case 'select':
            {
                if (!isset($this->post['parentId'])) array_push($this->response, 'parentId required');
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');

                if (count($this->response) > 0) {
                    exit(json_encode([
                        'result' => false,
                        'error' => $this->response,
                        'action' => 'SELECTION_ERROR'
                    ]));
                }
                break;
            }
            case 'delete':
            {
                if (!isset($this->post['id'])) array_push($this->response, 'id required');
                if (!isset($this->post['userId'])) array_push($this->response, 'userId required');

                if (count($this->response) > 0) {
                    exit(json_encode([
                        'result' => false,
                        'error' => $this->response,
                        'action' => 'DELETION_ERROR'
                    ]));
                }
                break;
            }
        }
        return true;
    }

    public function insertCategory()
    {
        $stmt = 'INSERT INTO `tbl_category` (`name`, `parent_id`, `user_id`) values (?,?,?)';
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('sii', $this->post['name'], $this->post['parentId'], $this->post['userId']);
        $result = $stmt->execute();
        if ($result) {
            echo json_encode(
                [
                    'result' => true,
                    'msg' => 'category inserted',
                    'action' => 'INSERTED'
                ]
            );
        }
    }

    public function selectCategory()
    {
        $stmt = "SELECT * FROM `tbl_category` where `parent_id` = ? and `user_id` = ? and`deleted` = false";
        $stmt = $this->conn->prepare($stmt);
        $stmt->bind_param('ii', $this->post['parentId'], $this->post['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = array();

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        echo json_encode(
            [
                'result' => true,
                'categories' => $categories,
                'action' => 'SELECTED'
            ]
        );
    }

    public function deleteCategory()
    {
        $stmt = "UPDATE `tbl_category` set `deleted` = true where `user_id` = ? and `id` = ?";
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
