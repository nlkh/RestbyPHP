<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// DEFINE RETURN MESSAGE FORM
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

// DECODING JSON PARAMETER(email, pw)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    // $returnData = msg(0,404,'Page Not Found!');
    $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email)
    || !isset($data->pw)
    || empty(trim($data->email))
    || empty(trim($data->pw))
    ):

    $fields = ['fields' => ['email','pw']];
    // $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    $returnData = msg(0,422,'필수 항목을 모두 입력해주세요.');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $email = trim($data->email);
    $pw = trim($data->pw);

    // CHECKING THE EMAIL FORMAT (IF INVALID FORMAT)
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        // $returnData = msg(0,422,'Invalid Email Address!');
        $returnData = msg(0,422,'적절하지 않은 이메일주소입니다.');

    // IF EMAIL IS VALIDATE
    else:
        try{
            // FETCH USER BY EMAIL
            $fetch_user_by_email = "SELECT * FROM `member` WHERE `email`=:email";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY EMAIL
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);

                // VERIFYING THE PASSWORD (IS CORRECT OR NOT?)
                $check_pw = password_verify($pw, $row['pw']);

                // IF PASSWORD IS CORRECT
                if($check_pw):
                  $check_cert = ($row['cert']==1);

                  // IF EMAIL ADDRESS HAS BEEN AUTHORIZED
                  if($check_cert) :
                    $member_no = $row['member_no'];

                    // FIND PAST REFRESHTOKEN
                    $existcheck_query = "SELECT member_no, isexpired, jwt from refreshjwt where member_no = :member_no and isexpired = 0";
                    $existcheck_stmt = $conn->prepare($existcheck_query);
                    $existcheck_stmt->bindValue(':member_no', $member_no, PDO::PARAM_INT);
                    $existcheck_stmt->execute();

                    // IF THE OTHER REFRESH TOKENS ARE NOT EXIST
                    if(!$existcheck_stmt->rowCount()) :
                      // ISSUING A REFRESH TOKEN
                      $jwt = new RefreshJwt();
                      $token = $jwt->_jwt_encode_data(
                          'http://localhost/RestAPI/',
                          array("user_id"=> $row['member_no'])
                      );

                      // INSERT ISSUED REFRESH TOKEN INTO DATABASE TABLE
                      $insert_query = "INSERT INTO refreshjwt (member_no, jwt, expiretime) VALUES(:member_no, :refreshjwt, :expiredate)";
                      $insert_stmt = $conn->prepare($insert_query);
                      $insert_stmt->bindValue(':member_no', $member_no, PDO::PARAM_INT);
                      $insert_stmt->bindValue(':refreshjwt', $token, PDO::PARAM_STR);
                      $insert_stmt->bindValue(':expiredate', date("Y-m-d H:i:s", time()+1209600), PDO::PARAM_STR);
                      $insert_stmt->execute();

                      $returnData = [
                          'success' => 1,
                          // 'message' => 'You successfully get refresh token.',
                          'message' => 'Refresh token이 발급되었습니다.',
                          'refresh' => $token,
                      ];

                    // IF USER ALREADY HAVE EFFECTIVE REFRESH JWT
                    else :
                          // $returnData = msg(0,422,'You Already Have Effective Refresh JWT!');
                          // $returnData = msg(0,422,'이미 유효한 Refresh token이 있습니다.');
                          $row = $existcheck_stmt->fetch();
                          $returnData = [
                              'success' => 1,
                              // 'message' => 'You successfully get refresh token.',
                              'message' => 'Refresh token이 발급되었습니다.',
                              'refresh' => $row['jwt']
                          ];
                    endif;

                  // IF THE USER DIDN'T AUTHORIZE EMAIL ADDRESS
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

echo json_encode($returnData, JSON_UNESCAPED_UNICODE);

?>
