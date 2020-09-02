<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__.'/classes/Database.php';
require_once __DIR__.'/middlewares/Auth.php';

// PARAMETER DECODING, PARAMETER : pw(NEW PASSWORD)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// DEFINITION RETURN MESSAGE FORM
function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

// HEADER, DB CONNECTION
$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// ACCESS TOKEN AUTHORIZE
$auth = new Auth($conn,$allHeaders);
$authReturn = $auth->isAuth();

// IF REQUEST METHOD IS NOT POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    // $returnData = msg(0,404,'Page Not Found!');
    $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->pw)
    || empty(trim($data->pw))
    ):

    // $returnData = msg(0,422,'Please Fill In The Password Field.')
    $returnData = msg(0,422,'새로운 비밀번호를 입력해주세요.');

elseif(!$authReturn) :
    // $returnData = msg(0,422,'Unauthorized');
    $returnData = msg(0,422,'세션이 만료되었습니다. 다시 로그인 하세요.');

// IF AUTHORIZE AND FILL THE PW FIELD
else:
    $pw = trim($data->pw);

    try{
        // UPDATE THE PASSWORD OF THE USER
        $query = "UPDATE member set pw = :pw where member_no=:member_no";
        $query_stmt = $conn->prepare($query);
        $query_stmt->bindValue(':pw', password_hash($pw, PASSWORD_DEFAULT),PDO::PARAM_STR);
        $query_stmt->bindValue(':member_no', $authReturn['user']['member_no'],PDO::PARAM_STR);
        $query_stmt->execute();

        // $returnData = msg(1,201,'Your password has been successfully changed. Please login with your new password.');
        $returnData = msg(1,201,'비밀번호가 변경되었습니다. 다시 로그인 하세요.');

        // EXPIRE THE ISSUED EFFECTIVE REFRESH TOKEN
        $query = "UPDATE refreshjwt set isexpired=1, expiretime=current_timestamp() where member_no=:member_no";
        $query_stmt = $conn->prepare($query);
        $query_stmt->bindValue(':member_no', $authReturn['user']['member_no'],PDO::PARAM_INT);
        $query_stmt->execute();

    }
    catch(PDOException $e){
        // $returnData = msg(0,500,$e->getMessage());
        $returnData = msg(0,500,'서버에 접속할 수 없습니다.');
    }

endif;

echo json_encode($returnData);

?>
