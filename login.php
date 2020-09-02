<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// DEFINITION RETURN MESSAGE FORM
function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

require_once __DIR__.'/classes/Database.php';
require_once __DIR__.'/classes/JwtHandler.php';
require_once __DIR__.'/classes/RefreshJwt.php';

// DB CONNECTION
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// INPUT JSON DECODING
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
//    $returnData = msg(0,404,'Page Not Found!');
      $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email)
    || !isset($data->pw)
    || empty(trim($data->email))
    || empty(trim($data->pw))
    ):

    $fields = ['fields' => ['email','pw']];
//    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    $returnData = msg(0,422,'필수 항목을 모두 입력해주세요.');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $email = trim($data->email);
    $pw = trim($data->pw);

    // CHECKING THE EMAIL FORMAT (IF INVALID FORMAT)
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
//        $returnData = msg(0,422,'Invalid Email Address!');
        $returnData = msg(0,422,'적절하지 않은 이메일주소입니다.');

    // THE USER IS ABLE TO PERFORM THE LOGIN ACTION
    else:
        try{
            // FETCH USER BY EMAIL PARAMETER
            $fetch_user_by_email = "SELECT * FROM `member` WHERE `email`=:email and isWithdrawal=0";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY EMAIL, VERIFY THE PASSWORD(CORRECT OR NOT)
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                $check_pw = password_verify($pw, $row['pw']);

                // IF PASSWORD IS CORRECT THEN CHECKING WHETHER EMAIL AUTHORIZATION HAS BE DONE OR NOT
                if($check_pw):
                  $check_cert = ($row['cert']==1);

                  // IF EMAIL AUTHORIZATION HAS BE DONE, SEND A NEW ACCESS TOKEN
                  if($check_cert) :
                    $jwtA = new JwtHandler();
                    $tokenA = $jwtA->_jwt_encode_data(
                        'http://localhost/RestAPI/',
                        array("user_id"=> $row['member_no'])
                    );

                    $returnData = [
                        'success' => 1,
                        //'message' => 'You have successfully logged in.',
                        'message' => '로그인 되었습니다.',
                        'access' => $tokenA
                    ];

                    // IF EMAIL AUTHORIZATION HAS NOT BE DONE
                    else :
                        // $returnData = msg(0,422,'Please Authorize Your Email First!');
                        $returnData = msg(0,422,'이메일 인증을 먼저 해주세요.');
                    endif;

                // IF INVALID PASSWORD
                else:
                    // $returnData = msg(0,422,'Invalid Password!');
                    $returnData = msg(0,422,'비밀번호가 틀렸습니다.');
                endif;

            // IF THE USER IS NOT FOUNDED BY EMAIL THEN SHOW THE FOLLOWING ERROR
            else:
                // $returnData = msg(0,422,'Invalid Email Address!');
                $returnData = msg(0,422,'등록되지 않은 이메일 계정입니다.');
            endif;
        }

        catch(PDOException $e){
            // $returnData = msg(0,500,$e->getMessage());
            $returnData = msg(0,500,'서버에 접속할 수 없습니다.');
        }

    endif;

endif;

echo json_encode($returnData);
?>
