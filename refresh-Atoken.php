<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__.'/classes/Database.php';
require_once __DIR__.'/middlewares/AuthbyRefreshJwt.php';

// GET HEADER(AUTHORIZATION REFRESH TOKEN)
$allHeaders = getallheaders();

// DB CONNECTION
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// AUTHORIZE REFRESH TOKEN
$auth = new AuthbyRefreshJwt($conn,$allHeaders);

$returnData = [
    "success" => 0,
    "status" => 401,
    // "message" => "Unauthorized"
    "message" => '인증 실패'
];

// RETURN AUTH RESULT
$authReturn = $auth->isAuth();
if(isset($authReturn)){
    $returnData = $authReturn;
}

echo json_encode($returnData);
?>
