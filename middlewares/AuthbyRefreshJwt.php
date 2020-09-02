<?php
require_once __DIR__.'/../classes/RefreshJwt.php';
require_once __DIR__.'/../classes/JwtHandler.php';

class AuthbyRefreshJwt extends RefreshJwt{
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
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))) {
            $this->token = explode(" ", trim($this->headers['Authorization']));

            // IF KEY EXIST IN HEADER
            if(isset($this->token[1]) && !empty(trim($this->token[1]))) {
                // SEARCH JWT IN DATABASE TABLE
                $insert_query = "SELECT isexpired from refreshjwt where jwt = :refreshjwt";
                $insert_stmt = $this->db->prepare($insert_query);
                $insert_stmt->bindValue(':refreshjwt', $this->token[1], PDO::PARAM_STR);
                $insert_stmt->execute();

                // FETCH FAILED
                if($insert_stmt->rowcount()==0) {
                    return null;
                }

                // IF RECORDS OF RESULT ARE MORE THAN 1
                elseif($insert_stmt->rowcount()!=1) {
                  // ALL EXPIRE JWT
                  $insert_query = "UPDATE refreshjwt set isexpired = 1, expiretime = current_timestamp() where jwt = :refreshjwt";
                  $insert_stmt = $this->db->prepare($insert_query);
                  $insert_stmt->bindValue(':refreshjwt', $this->token[1], PDO::PARAM_STR);
                  $insert_stmt->execute();
                  return [
                      'success' => 0,
                      'status' => 422,
                      // 'message' => 'Unexpected Access. Please login again with email and password.'
                      'message' => '잘못된 접근입니다. 아이디, 비밀번호를 이용해 로그인 해주세요.'
                  ];
                }

                // SET RESULT INTO WHETHER EXPIRED OR NOT
                $result = $insert_stmt->fetch();
                $result = (int) $result['isexpired'];

                // IF HAS NOT EXPIRED
                if($result == 0) {
                  // TOKEN DECODING
                  $data = $this->_jwt_decode_data($this->token[1]);

                  // IF DATA ARE EFFECTIVE AND AUTH PASS
                  if(isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) {
                      $user = $this->fetchUser($data['data']->user_id);

                      // EXPIRE USED JWT
                      $insert_query = "UPDATE refreshjwt set isexpired = :isexpired, expiretime = now() where jwt = :refreshjwt";
                      $insert_stmt = $this->db->prepare($insert_query);
                      $insert_stmt->bindValue(':refreshjwt', $this->token[1],PDO::PARAM_STR);
                      $insert_stmt->bindValue(':isexpired', 1, PDO::PARAM_INT);
                      $insert_stmt->execute();

                      return $user;
                  }

                  // IF DATA NOT EFFECTIVE
                  else {
                    return null;
                  }
                }

                // TOKEN IS ALREADY EXPIRED
                else {
                  return null;
                }
              }

              // TOKEN IS NOT IN HEADER
              else {
                return null;
              }
            }

            // HEADER IS NOT EXIST
            else {
              return null;
            }
        }

    protected function fetchUser($user_id){
        try{
            // FETCH USER BY USER_ID IN TOKEN
            $fetch_user_by_id = "SELECT member_no, name, email FROM member WHERE member_no=:id and isWithdrawal=0";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id,PDO::PARAM_INT);
            $query_stmt->execute();

            // FETCH OK
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);

                // ACCESS, REFRESH TOKEN ISSUING
                $jwtA = new JwtHandler();
                $tokenA = $jwtA->_jwt_encode_data(
                    'http://localhost/RestAPI/',
                    array("user_id"=> $row['member_no'])
                );
                $jwtR = new RefreshJwt();
                $tokenR = $jwtR->_jwt_encode_data(
                    'http://localhost/RestAPI/',
                    array("user_id"=> $row['member_no'])
                );

                // INSERT REFRESH TOKEN INTO DATABASE TABLE
                $insert_query = "INSERT INTO refreshjwt(member_no, jwt, expiretime) VALUES(:member_no, :refreshjwt, :expiredate)";
                $insert_stmt = $this->db->prepare($insert_query);
                $insert_stmt->bindValue(':member_no', $row['member_no'],PDO::PARAM_INT);
                $insert_stmt->bindValue(':refreshjwt', $tokenR,PDO::PARAM_STR);
                $insert_stmt->bindValue(':expiredate', time()+1209600, PDO::PARAM_STR);
                $insert_stmt->execute();

                return [
                    'success' => 1,
                    'status' => 200,
                    'message' => '토큰이 재발급되었습니다.',
                    'access' => $tokenA,
                    'refresh' => $tokenR
                ];
            else:
                return null;
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    }
}
