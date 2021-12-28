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
                'error' => 'api type required'
            ]));
        }

        switch ($apiType) {
            case 'insert': {
                    // $categoryName = @$this->post['name'];
                    // $parentId = @$this->post['parentId'];
                    // $userId = @$this->post['userId'];
                    // $array = [];

                    if (isset($this->post['name'])) array_push($this->response, 'name required');
                    if (isset($this->post['parentId'])) array_push($this->response, 'parentId required');
                    if (isset($this->post['userId'])) array_push($this->response, 'userId required');

                    // array_push($array, $categoryName, $parentId, $userId);
                    //count($array) != 3
                    if (count($this->response) > 0) {
                        exit(json_encode([
                            'result' => false,
                            'error' => $this->response
                        ]));
                    }
                    break;
                }
            case 'select': {
                    //todo add select
                    break;
                }
            case 'delete': {
                    //todo add delete
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
                    'msg' => 'category inserted'
                ]
            );
        }
    }
}
