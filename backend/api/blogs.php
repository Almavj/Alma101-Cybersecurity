<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../models/Blog.php';
require_once '../middleware/auth.php';

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
        $userId = authenticate();
        $data = json_decode(file_get_contents("php://input"), true);
        $data['author_id'] = $userId;
        
        if($blog->create($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Blog created successfully']);
        } else {
            http_response_code(503);
            echo json_encode(['message' => 'Unable to create blog']);
        }
        break;

    case 'PUT':
        $userId = authenticate();
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
        $userId = authenticate();
        
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