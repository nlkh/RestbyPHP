<?php
require_once __DIR__.'/../jwt/JWT.php';
require_once __DIR__.'/../jwt/ExpiredException.php';
require_once __DIR__.'/../jwt/SignatureInvalidException.php';
require_once __DIR__.'/../jwt/BeforeValidException.php';

use \Firebase\JWT\JWT;

class JwtHandler {
  protected $jwt_secrect;
  protected $token;
  protected $issuedAt;
  protected $expire;
  protected $jwt;

  public function __construct() {
    date_default_timezone_set('Asia/Seoul');
    $this->issuedAt = time();

    $this->expire = $this->issuedAt + 3600;

    $this->jwt_secrect = "this_is_my_secrect";
  }

  public function _jwt_encode_data($iss, $data) {
    $this->token = array(
      "iss" => $iss,
      "aud" => $iss,
      "iat" => $this->issuedAt,
      "exp" => $this->expire,
      "data"=> $data
    );
    $this->jwt = JWT::encode($this->token, $this->jwt_secrect);
    return $this->jwt;
  }

  protected function _errMsg($msg) {
    return ["auth" => 0, "message" => $msg];
  }

  public function _jwt_decode_data($jwt_token) {
    try {
      $decode = JWT::decode($jwt_token, $this->jwt_secrect, array('HS256'));
      return ["auth" => 1, "data" => $decode->data];
    }
    catch(\Firebase\JWT\ExpiredException $e) {
      return $this->_errMsg($e->getMessage());
    }
    catch(\Firebase\JWT\SignatureInvalidException $e) {
      return $this->_errMsg($e->getMessage());
    }
    catch(\DomainException $e){
      return $this->_errMsg($e->getMessage());
    }
    catch(\InvalidArgumentException $e){
      return $this->_errMsg($e->getMessage());
    }
    catch(\UnexpectedValueException $e){
      return $this->_errMsg($e->getMessage());
    }
  }
} ?>
