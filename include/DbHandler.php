<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Aibol Maxotov
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $phone, $email, $password, $device_id) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO `user`(`name`, `phone`, `email`, `password`, `c_date`, `m_date`, `api_key`, `device_id`) VALUES (?,?,?,?,now(),now(),?,?)");
            $stmt->bind_param("ssssss", $name, $phone, $email, $password_hash, $api_key, $device_id);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password FROM user WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, phone, email, c_date, m_date, api_key, device_id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $phone, $email, $c_date, $m_date, $api_key, $device_id);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["phone"] = $phone;
            $user["email"] = $email;
            $user["c_date"] = $c_date;
            $user["m_date"] = $m_date;
            $user["api_key"] = $api_key;
            $user["device_id"] = $device_id;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM user WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM user WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from user WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

   /**
    * get goods
    */
   public function getGoods(){
       $stmt = $this->conn->prepare("SELECT g.* FROM goods g ");
       $stmt->execute();
       $goods = $stmt->get_result();
       $stmt->close();
       return $goods;
   }

   /**
    * get photo
    */
    public function getPhoto($goods_id){
        $stmt = $this->conn->prepare("SELECT p.* FROM photo p where p.goods_id=?");
        $stmt->bind_param('i',$goods_id);
        $stmt->execute();
        $photos = $stmt->get_result();
        $stmt->close();
        return $photos;
    }

    /**
    * find Goods
    */
   public function findGoodsByID($id,$type)
   {
       $stmt =($type=="goods_id")?($this->conn->prepare("SELECT g.* FROM goods g where g.id=?")):($this->conn->prepare("SELECT g.* FROM goods g where g.shop_id=?"));
       $stmt->bind_param('i',$id);
       $stmt->execute();
       $photos = $stmt->get_result();
       $stmt->close();
       return $photos;
   }

    /**
     * find by name
     */
    public function findGoodsByName($name){

        $stmt = $this->conn->prepare("SELECT g.* FROM goods g where UPPER(g.name) LIKE ?");
        $stmt->bind_param('s',$name);
        $stmt->execute();
        $result= $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * find by name
     */
    public function findGoodsByPrice($price){

        $stmt = $this->conn->prepare("SELECT g.* FROM goods g where g.price=?");
        $stmt->bind_param('i',$price);
        $stmt->execute();
        $result= $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Insert Goods
     */
    public function insertGoods($name,$price,$description,$shop_id){

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO `goods`(`name`, `price`, `description`, `shop_id`, `c_date`, `m_date`) VALUES (?,?,?,?,now(),now())");
            $stmt->bind_param("sisi", $name, $price,$description,$shop_id);

            $result = $stmt->execute();
            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                $new_goods_id=$this->conn->insert_id;
                return $new_goods_id;
            } else {
                // Failed to create user
                return null;
            }

        return null;
    }

    /**
     * update goods
     */
    public function updateGoods($goods_id,$name,$price,$description){
        // update query
        $stmt = $this->conn->prepare("UPDATE  goods SET name=?, price=?, description=? , m_date=now() WHERE id=?");
        $stmt->bind_param("sisi", $name, $price,$description,$goods_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * delete goods
     */
    public function deleteGoods($goods_id){
        $stmt = $this->conn->prepare("DELETE FROM goods WHERE id= ?");
        $stmt->bind_param("i",$goods_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * delete photo
     */
    public function deletePhotoByIdOrGoodsId($id,$type){

        $stmt =($type!="all")?($this->conn->prepare("DELETE FROM photo WHERE id=?")):($this->conn->prepare("DELETE FROM photo WHERE goods_id=?"));
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Insert Photo
     */
    public function insertPhoto($name,$description,$goods_id){
           // insert query
           $stmt=$this->conn->prepare("INSERT INTO photo(name,description,goods_id, c_date,m_date) VALUES (?,?,?,now(),now())");

           $stmt->bind_param("sss",$name, $description, $goods_id);
           $result=$stmt->execute();
           $stmt->close();

        // Check for successfully inserting
        if($result){
            $new_id=$this->conn->insert_id;
            return $new_id;
        }else{
            return null;
        }

        return null;
    }

    /**
     * get image path using mall - shop - goods ->ids
     */
    public function getImagePath($goods_id){

        $stmt = $this->conn->prepare("SELECT m.id as m_id , s.id as s_id from mall m , shop s ,goods g WHERE m.id=s.mall_id AND s.id=g.shop_id AND g.id = ?");
        $stmt->bind_param("i",$goods_id);

        if ($stmt->execute()) {
            $task = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }

    }
}

?>
