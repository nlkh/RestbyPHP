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

// INCLUDING DATABASE AND MAKING OBJECT
require_once __DIR__.'/classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// DECODING JSON PARAMETER(name, email, phone, pw)
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    // $returnData = msg(0,404,'Page Not Found!');
    $returnData = msg(0,404,'잘못된 접근입니다.');

// CHECKING EMPTY FIELDS
elseif(!isset($data->name)
    || !isset($data->email)
    || !isset($data->phone)
    || !isset($data->pw)
    || empty(trim($data->name))
    || empty(trim($data->email))
    || empty(trim($data->phone))
    || empty(trim($data->pw))
    ):

    $fields = ['fields' => ['name','email','phone','pw']];
    // $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    $returnData = msg(0,422,'필수 항목을 모두 입력해주세요.');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $name = trim($data->name);
    $email = trim($data->email);
    $phone = trim($data->phone);
    $pw = trim($data->pw);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        // $returnData = msg(0,422,'Invalid Email Address!');
        $returnData = msg(0,422,'적절하지 않은 이메일주소입니다.');

    elseif(strlen($name) < 2):
        // $returnData = msg(0,422,'Your name must be at least 2 characters long!');
        $returnData = msg(0,422,'이름은 2글자 이상 입력하세요.');

    else:
        try{
            // EMAIL DUPLICATION CHECK
            $check_email = "SELECT `email` FROM `member` WHERE `email`=:email";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):
                // $returnData = msg(0,422, 'This E-mail already in use!');
                $returnData = msg(0,422, '이미 등록된 이메일입니다.');

            // EMAIL NON-DUPLICATION
            else:
                // REGESTERING
                $insert_query = "INSERT INTO `member`(`name`,`email`,`phone`,`pw`) VALUES(:name,:email,:phone,:pw)";

                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindValue(':name', htmlspecialchars(strip_tags($name)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                $insert_stmt->bindValue(':phone', $phone,PDO::PARAM_STR);
                $insert_stmt->bindValue(':pw', password_hash($pw, PASSWORD_DEFAULT),PDO::PARAM_STR);
                $insert_stmt->execute();

                // SEND MAIL
                require_once __DIR__.'/SMTP/send_mail.php';
                $cert_url = hash("sha256", $email);
                $cert_query = "UPDATE member set cert_url=:cert_url where email=:email";
                $cert_stmt = $conn->prepare($cert_query);
                $cert_stmt->bindValue(':cert_url', $cert_url, PDO::PARAM_STR);
                $cert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $cert_stmt->execute();
                sendCertMail($cert_url, $email);

                // $returnData = msg(1,201,'You have successfully registered. Please Authorize Your Email Address Before Login.');
                $returnData = msg(1,201,'회원가입이 완료되었습니다. 등록된 이메일로 발송된 메일을 통해 인증을 진행해주세요.');
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
