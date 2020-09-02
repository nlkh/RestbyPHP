<?php
function getPDO(){
    $db_host = "localhost";
    $db_id = "root";
    $db_pw = "";
    $dsn = "mysql:host=$db_host;dbname=ngn";

    try {
        $pdo = new PDO($dsn, $db_id, $db_pw);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//         echo "Conn success.\n";

        return $pdo;
    } catch(PDOException $e) {
//         echo "Conn fail error massage => ";
//         echo $e->getMessage()."\n";
    }
}


?>
