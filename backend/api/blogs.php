<?php
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Blog.php';
require_once __DIR__ . '/../middleware/auth.php';

$database = new Database();
$blog = new Blog($database);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            $result = $blog->getById($_GET['id']);
            if($result) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Blog not found']);
            }
        } else {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $result = $blog->getAll($limit, $page);
            http_response_code(200);
            echo json_encode($result);
        }
        break;

    case 'POST':
        // only admin can create
        $adminId = authenticateAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        $data['author_id'] = $adminId;
        
        if($blog->create($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Blog created successfully']);
        } else {
            http_response_code(503);
            echo json_encode(['message' => 'Unable to create blog']);
        }
        break;

    case 'PUT':
        $adminId = authenticateAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(isset($_GET['id'])) {
            if($blog->update($_GET['id'], $data)) {
                http_response_code(200);
                echo json_encode(['message' => 'Blog updated successfully']);
            } else {
                http_response_code(503);
                echo json_encode(['message' => 'Unable to update blog']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Blog ID not provided']);
        }
        break;

    case 'DELETE':
        $adminId = authenticateAdmin();
        
        if(isset($_GET['id'])) {
            if($blog->delete($_GET['id'])) {
                http_response_code(200);
                echo json_encode(['message' => 'Blog deleted successfully']);
            } else {
                http_response_code(503);
                echo json_encode(['message' => 'Unable to delete blog']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Blog ID not provided']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}