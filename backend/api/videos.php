<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../models/Video.php';
require_once '../middleware/auth.php';

$database = new Database();
$video = new Video($database);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            $result = $video->getById($_GET['id']);
            if($result) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Video not found']);
            }
        } else {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $result = $video->getAll($limit, $page);
            http_response_code(200);
            echo json_encode($result);
        }
        break;

    case 'POST':
        // only admin can create
        $adminId = authenticateAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        $data['author_id'] = $adminId;
        
        if($video->create($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Video created successfully']);
        } else {
            http_response_code(503);
            echo json_encode(['message' => 'Unable to create video']);
        }
        break;

    case 'PUT':
        $adminId = authenticateAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(isset($_GET['id'])) {
            if($video->update($_GET['id'], $data)) {
                http_response_code(200);
                echo json_encode(['message' => 'Video updated successfully']);
            } else {
                http_response_code(503);
                echo json_encode(['message' => 'Unable to update video']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Video ID not provided']);
        }
        break;

    case 'DELETE':
        $adminId = authenticateAdmin();
        
        if(isset($_GET['id'])) {
            if($video->delete($_GET['id'])) {
                http_response_code(200);
                echo json_encode(['message' => 'Video deleted successfully']);
            } else {
                http_response_code(503);
                echo json_encode(['message' => 'Unable to delete video']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Video ID not provided']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
