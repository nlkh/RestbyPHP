<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__.'/classes/Database.php';

// DB CONNECTION
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// DECODING JSON PARAMETER(EMAIL)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];
// IF REQUEST METHOD IS NOT EQUAL TO POST

// CHECKING EMPTY FIELDS
if(!isset($data->email)
    || empty(trim($data->email))
    ):

    $returnData = [
      'success' => '0',
      'status' => 422,
      //'message' => 'Please Fill in a email!'
      'message' => '이메일을 입력하세요.'
    ];

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $email = trim($data->email);

    // CHECKING THE EMAIL FORMAT (IF INVALID FORMAT)
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = [
          'success' => '0',
          'status' => 422,
          'message' => '사용할 수 없는 이메일 입니다.'
          // 'message' => 'Invalid Email Address!'];

    // IF THE EMAIL ADDRESS IS VALIDATE
    else:
        try{
            // SEARCH EMAIL IN TABLE
            $fetch_user_by_email = "SELECT email FROM `member` WHERE `email`=:email";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY EMAIL
            if($query_stmt->rowCount()):
                    $returnData = [
                        'success' => 1,
                        'reduplication' => 1,
                        // 'message' => 'This email is already existed.'
                        'message' => '이미 등록된 이메일 주소 입니다.'
                    ];
            // THE EMAIl ADDRESS IS NOT IN TABLE
            else :
                    $returnData = [
                      'success' => 1,
                      'reduplication' => 0,
                      // 'message' => 'This email can be registered.'
                      'message' => '사용할 수 있는 이메일 주소 입니다.'
                    ];
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
