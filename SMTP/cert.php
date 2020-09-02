<?php
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    require("pdo.php");
    if(isset($_GET['cert'])){
        $pdo = getPDO();
        $cert = $_GET['cert'];
        $query = "SELECT email from member where cert_url='$cert';";

        $psmtm = $pdo->prepare($query);
        $psmtm->execute();

        $member_id = $psmtm->fetchAll(PDO::FETCH_BOTH)[0]['email'];

        $query = "UPDATE member SET cert=? WHERE email=?";

        $psmtm= $pdo->prepare($query);
        $psmtm->execute([1,$member_id]);
        $psmtm->execute();

        echo "<script>alert('이메일 인증이 완료되었습니다. 다시 로그인 해주세요.');window.close();</script>";
    }
    else{
        echo "<script>alert('잘못된 접근입니다.');window.close();</script>";
    }
?>
