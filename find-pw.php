<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// DEFINE RETURn MESSAGE FORM
function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

require_once __DIR__.'/classes/Database.php';
require_once __DIR__.'/SMTP/send_mail.php';

// DB Connection
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// DECODING JSON PARAMETER(name, email)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    // $returnData = msg(0,404,'Page Not Found!');
    $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->name)
    || !isset($data->email)
    || empty(trim($data->name))
    || empty(trim($data->email))
    ):

    $fields = ['fields' => ['name','email']];
    // $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    $returnData = msg(0,422,'필수 항목을 모두 입력해주세요.');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $name = trim($data->name);
    $email = trim($data->email);

    try{
            // FETCH USER WHO IS TRYING TO FIND EMAIL ADDRESS
            $fetch_user = "SELECT member_no FROM member WHERE name=:name and email=:email and isWithdrawal=0";
            $query_stmt = $conn->prepare($fetch_user);
            $query_stmt->bindValue(':name', $name,PDO::PARAM_STR);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                $member_no = $row['member_no'];

                // ISSUING TEMPORARY PASSWORD AND SENDING IT TO THE USER ON EMAIL
                date_default_timezone_set('Asia/Seoul');
                $time = time();
                $tempPassword = substr(hash('sha256', (string)$member_no.(string)$time),0,10);

                sendPWMail($tempPassword, $email);

                // SET TEMPORARY PASSWORD INTO DATABASE
                $set_pw_query = "UPDATE member set pw=:pw where member_no=:member_no";
                $set_pw_stmt = $conn->prepare($set_pw_query);
                $set_pw_stmt->bindValue(':pw', password_hash($tempPassword, PASSWORD_DEFAULT),PDO::PARAM_STR);
                $set_pw_stmt->bindValue(':member_no', $member_no,PDO::PARAM_INT);
                $set_pw_stmt->execute();

                // EXPIRE ALL REFRESHJWT OF THE USER
                $expire_jwt_query = "UPDATE refreshjwt set isexpired=1, expiretime=current_timestamp() where member_no=:member_no";
                $expire_jwt_stmt = $conn->prepare($expire_jwt_query);
                $expire_jwt_stmt->bindValue(':member_no', $member_no, PDO::PARAM_INT);
                $expire_jwt_stmt->execute();

                $returnData = [
                    'success' => 1,
                    'status' => 201,
                    // 'message' => 'Temporary Password was sent to your email.',
                    'message' => '등록된 이메일로 임시 비밀번호가 발송되었습니다.'
                ];
              // FETCH FAILED
              else:
                // $returnData = msg(0,422,"Cannot find matched user info");
                $returnData = msg(0,422,"등록되지 않은 회원입니다.");
              endif;
        }
        catch(PDOException $e){
            // $returnData = msg(0,500,$e->getMessage());
            $returnData = msg(0,500,'서버에 접속할 수 없습니다.');
        }
endif;
echo json_encode($returnData);
?>
