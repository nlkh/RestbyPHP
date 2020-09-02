<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__.'/classes/Database.php';
require_once __DIR__.'/middlewares/Auth.php';

$returnData = [];

// DEFINE RETURN MESSAGE FORM
function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

// GET HEADER(AUTHORIZATION ACCESS TOKEN)
$allHeaders = getallheaders();

// DB CONNECTION
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// AUTHORIZE ACCESS TOKEN
$auth = new Auth($conn,$allHeaders);
$authReturn = $auth->isAuth();

// IF AUTHORIZATION FAILED
if(!$authReturn || !isset($authReturn)) :
    // $returnData = msg(0,422,'Unauthorized');
    $returnData = msg(0,422,'인증 실패');

// IF AUTHORIZATION PASS
else:
        try{
            // EXPIRE ALL OF ISSUED REFRESH TOKEN
            $query = "UPDATE refreshjwt set isexpired=1, expiretime=current_timestamp() where member_no=:member_no";
            $query_stmt = $conn->prepare($query);
            $query_stmt->bindValue(':member_no', $authReturn['user']['member_no'],PDO::PARAM_INT);
            $query_stmt->execute();

            // WITHDRAWAL
            $query = "UPDATE member set isWithdrawal=1 where member_no=:member_no";
            $query_stmt = $conn->prepare($query);
            $query_stmt->bindValue(':member_no', $authReturn['user']['member_no'],PDO::PARAM_INT);
            $query_stmt->execute();

            // $returnData = msg(1,201,'Withdrawal Finish');
            $returnData = msg(1,201,'회원 탈퇴가 완료되었습니다. 지금까지 오늘 할 일 내일로를 이용해 주셔서 감사합니다.');
        }
        catch(PDOException $e){
            // $returnData = msg(0,500,$e->getMessage());
            $returnData = msg(0,500,'서버에 접속할 수 없습니다.');
        }
endif;

echo json_encode($returnData);

?>
