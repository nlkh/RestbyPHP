<?php
require_once __DIR__.'/../classes/JwtHandler.php';
class Auth extends JwtHandler{
    protected $db;
    protected $headers;
    protected $token;
    public function __construct($db,$headers) {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

    public function isAuth(){
        // IF HEADER EXIST
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));

            // IF KEY EXIST IN HEADER
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):
                $data = $this->_jwt_decode_data($this->token[1]);

                // IF DECODED JWT DATA EFFECTIVE
                if(isset($data['auth'])&& isset($data['data']->user_id)&& $data['auth']):
                    // FETCH USER AND RETURN USER INFORMATION
                    $user = $this->fetchUser($data['data']->user_id);
                    return $user;

                // IF DECODED JWT DATA UNEFFECTIVE
                else:
                    return null;
                endif;

            // IF KEY IS NOT IN HEADER
            else:
                return null;
            endif;

        // IF HEADER DO NOT EXIST
        else:
            return null;

        endif;
    }

    protected function fetchUser($user_id){
        try{
            // FETCH USER INFORMATION BY INFORMATION IN TOKEN
            $fetch_user_by_id = "SELECT member_no, name, email, phone, signup_date FROM member WHERE member_no=:id and isWithdrawal=0";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id,PDO::PARAM_INT);
            $query_stmt->execute();

            // IF FETCH OK, RETURN INFORMATION IN DATABASE TABLE
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'success' => 1,
                    'status' => 200,
                    'message' => '인증되었습니다.',
                    'user' => $row
                ];

            // IF FETCH FAILED, NO RETURN
            else:
                return null;
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    }
}
