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

// DB CONNECTION
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// DECODING JSON PARAMETER(name, phone)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    // $returnData = msg(0,404,'Page Not Found!');
    $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->name)
    || !isset($data->phone)
    || empty(trim($data->name))
    || empty(trim($data->phone))
    ):

    $fields = ['fields' => ['name','phone']];
    // $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    $returnData = msg(0,422,'필수 항목을 모두 입력해주세요.');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $name = trim($data->name);
    $phone = trim($data->phone);

    try{
            // FIND EMAIL WHICH IS MATCHED WITH BOTH NAME AND PHONE AND HAS NOT WITHDRAWAL
            $fetch_user = "SELECT email FROM member WHERE name=:name and phone=:phone and isWithdrawal=0";
            $query_stmt = $conn->prepare($fetch_user);
            $query_stmt->bindValue(':name', $name,PDO::PARAM_STR);
            $query_stmt->bindValue(':phone', $phone,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY THE PARAMETERS, RETURN ALL OF MATCHED EMAIL
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetchAll(PDO::FETCH_ASSOC);
                $emaillist=array();
                foreach($row as $element) {
                  $emaillist[] = $element["email"];
                }
                    $returnData = [
                        'success' => 1,
                        // 'message' => 'Successfully found your emails.',
                        'message' => '해당 정보로 가입된 이메일 주소는 다음과 같습니다.'
                        'email' => $emaillist
                    ];
            else :
                // $returnData = msg(0,422,'Cannot find your email!');
                $returnData = msg(0,422,'해당 정보로 가입된 이메일 주소는 없습니다.');
            endif;
        }
        catch(PDOException $e){
            // $returnData = msg(0,500,$e->getMessage());
            $returnData = msg(0,500,'서버에 접속할 수 없습니다.');
        }
endif;

echo json_encode($returnData);
?>
