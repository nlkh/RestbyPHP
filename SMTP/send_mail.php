<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require(__DIR__."/../PHPMailer/src/PHPMailer.php");
    require(__DIR__."/../PHPMailer/src/SMTP.php");
    require(__DIR__."/../PHPMailer/src/Exception.php");

    function sendCertMail($cert_url, $address){
        $mail = new PHPMailer(true);
        try {
            $mail -> SMTPDebug = 2; // 디버깅 설정
            $mail -> isSMTP(); // SMTP 사용 설정
            $mail -> Host = "smtp.naver.com";
            $mail -> SMTPAuth = true;
            $mail -> Username = ""
            $mail -> Password = ""
            $mail -> SMTPSecure = "ssl";                       // SSL을 사용함
            $mail -> Port = 465;                                  // email 보낼때 사용할 포트를 지정
            $mail -> CharSet = "utf-8"; // 문자셋 인코딩
            // 보내는 메일
            $mail -> setFrom("", "오늘 할 일 내일로");
            // 받는 메일
            $mail -> addAddress($address, "receive01");
            // 첨부파일
            // 메일 내용
            $mail -> isHTML(true); // HTML 태그 사용 여부
            $mail -> Subject = "[오늘 할 일 내일로 가입 인증메일]오늘 할 일 내일로 가입을 환영합니다 !";  // 메일 제목
            $mail -> Body = "<div style = 'background-color:rgb(236, 239, 245); text-align:center;'>
        <div>
            <p style = 'font-size:24px;margin:12px;font-weight:bold;'>
                \"오늘 할 일 내일로 가입을 환영합니다 !\"
            </p>
            <p style = 'font-size:16px;margin:0px;'>
                오늘 할 일 내일로에서 여행을 자유롭게 계획하고, 공유하세요 !
            </p>
            <p style = 'font-size:16px;margin:0px;'>
                오늘 할 일 내일로는 깨끗한 커뮤니티 문화를 지향합니다. 기본적인 네티켓을 지키지 않을 시, 계정을 정지당할 수 있습니다.
            </p>
        </div>

        <p style ='font-size:18px;margin:12px;'>아래 URL을 클릭하여, 이메일 인증을 완료해주세요.<br><a href = 'http://localhost/RestAPI/SMTP/cert.php?cert=$cert_url'>이메일 인증하기</a></p>
    </div>";     // 메일 내용
            $mail -> SMTPOptions = array(
              "ssl" => array(
              "verify_peer" => false
              , "verify_peer_name" => false
              , "allow_self_signed" => true
              )
            );
            // 메일 전송
            $mail -> send();
            echo "Message has been sent";
            return true;
        }
        catch (Exception $e) {
            echo "Message could not be sent. Mailer Error : ", $mail -> ErrorInfo;
            return false;
        }
    }

    function sendPWMail($tempPassword, $address){
        $mail = new PHPMailer(true);
        try {
            $mail -> SMTPDebug = 2; // 디버깅 설정
            $mail -> isSMTP(); // SMTP 사용 설정
            $mail -> Host = "smtp.naver.com";
            $mail -> SMTPAuth = true;
            $mail -> Username = "";
            $mail -> Password = "";
            $mail -> SMTPSecure = "ssl";                       // SSL을 사용함
            $mail -> Port = 465;                                  // email 보낼때 사용할 포트를 지정
            $mail -> CharSet = "utf-8"; // 문자셋 인코딩
            // 보내는 메일
            $mail -> setFrom("", "오늘 할 일 내일로");
            // 받는 메일
            $mail -> addAddress($address, "receive01");
            // 첨부파일
            // 메일 내용
            $mail -> isHTML(true); // HTML 태그 사용 여부
            $mail -> Subject = "[오늘 할 일 내일로] 임시 비밀번호가 발급되었습니다.";  // 메일 제목
            $mail -> Body = "<div style = 'background-color:rgb(236, 239, 245); text-align:center;'>
        <div>
            <p style = 'font-size:24px;margin:12px;font-weight:bold;'>
                \"오늘 할 일 내일로 임시 비밀번호가 발급되었습니다.\"
            </p>
            <p style = 'font-size:16px;margin:0px;'>
                아래의 비밀번호로 로그인하신 후 회원님의 개인정보 보호를 위해 반드시 비밀번호를 변경해주세요!
            </p>
            <p style = 'font-size:16px;margin:0px;'>
                오늘 할 일 내일로는 깨끗한 커뮤니티 문화를 지향합니다. 기본적인 네티켓을 지키지 않을 시, 계정을 정지당할 수 있습니다.
            </p>
        </div>

        <p style ='font-size:18px;margin:12px;'>임시 비밀번호<br>$tempPassword</p>
    </div>";     // 메일 내용
            $mail -> SMTPOptions = array(
              "ssl" => array(
              "verify_peer" => false
              , "verify_peer_name" => false
              , "allow_self_signed" => true
              )
            );
            // 메일 전송
            $mail -> send();
            echo "Message has been sent";
            return true;
        }
        catch (Exception $e) {
            echo "Message could not be sent. Mailer Error : ", $mail -> ErrorInfo;
            return false;
        }
    }
?>
