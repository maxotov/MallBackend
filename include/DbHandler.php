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

            if($this->isDeviceExists($device_id)){
                $result = $this->updateUserByDevice($device_id, $name, $phone, $email, $password_hash, $api_key);
            } else {
            // insert query
                $stmt = $this->conn->prepare("INSERT INTO `user`(`name`, `phone`, `email`, `password`, `c_date`, `m_date`, `api_key`, `device_id`) VALUES (?,?,?,?,now(),now(),?,?)");
                $stmt->bind_param("ssssss", $name, $phone, $email, $password_hash, $api_key, $device_id);

                $result = $stmt->execute();

                $stmt->close();
            }
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

    public function createBasicUser($device_id) {
        $response = array();
        if (!$this->isDeviceExists($device_id)) {
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO `user`(`name`, `phone`, `email`, `password`, `c_date`, `m_date`, `api_key`, `device_id`) VALUES ('','','','',now(),now(),'',?)");
        $stmt->bind_param("s", $device_id);
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
            return DEVICE_ALREADY_EXISTED;
        }
        return $response;
    }

    public function updateUserByDevice($device_id, $name, $phone, $email, $password, $api_key) {
        $stmt = $this->conn->prepare("UPDATE user set name = ?, phone = ?, email = ?, password=?, m_date=now(), api_key=? WHERE device_id = ?");
        $stmt->bind_param("ssssss", $name, $phone, $email, $password, $api_key, $device_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
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
     * Checking for duplicate user by device id
     * @param String $device device to check in db
     * @return boolean
     */
    private function isDeviceExists($device) {
        $stmt = $this->conn->prepare("SELECT id from user WHERE device_id = ?");
        $stmt->bind_param("s", $device);
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
        $stmt = $this->conn->prepare("SELECT id, name, phone, email, c_date, m_date, api_key, device_id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            if($num_rows > 0){
                // $user = $stmt->get_result()->fetch_assoc();
                $stmt->bind_result($id, $name, $phone, $email, $c_date, $m_date, $api_key, $device_id);
                $stmt->fetch();
                $user = array();
                $user["id"] = $id;
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
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user by id
     * @param String id
     */
    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT id, name, phone, email, c_date, m_date, api_key, device_id FROM user WHERE id = ?");
        $stmt->bind_param("s", $userId);
        if ($stmt->execute()) {
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            if($num_rows > 0){
                // $user = $stmt->get_result()->fetch_assoc();
                $stmt->bind_result($id, $name, $phone, $email, $c_date, $m_date, $api_key, $device_id);
                $stmt->fetch();
                $user = array();
                $user["id"] = $id;
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
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user by id
     * @param String id
     */
    public function getUserByDevice($deviceId) {
        $stmt = $this->conn->prepare("SELECT id, name, phone, email, c_date, m_date, api_key, device_id FROM user WHERE device_id = ?");
        $stmt->bind_param("s", $deviceId);
        if ($stmt->execute()) {
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            if($num_rows > 0){
                // $user = $stmt->get_result()->fetch_assoc();
                $stmt->bind_result($id, $name, $phone, $email, $c_date, $m_date, $api_key, $device_id);
                $stmt->fetch();
                $user = array();
                $user["id"] = $id;
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
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user
     */
    public function getAllUser() {
        $stmt = $this->conn->prepare("SELECT * FROM user");
        $stmt->execute();
        $users = $stmt->get_result();
        $stmt->close();
        return $users;
    }

    /**
     * Deleting a user
     * @param String $task_id id of the task to delete
     */
    public function deleteUserById($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
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

    //----------------- Mall operations --------------------------
    /**
     * Fetching all mall
     */
    public function getAllMall() {
        $stmt = $this->conn->prepare("SELECT * FROM mall");
        $stmt->execute();
        $users = $stmt->get_result();
        $stmt->close();
        return $users;
    }

    //----------------- Category operations --------------------------
    /**
     * Fetching all mall
     */
    public function getAllCategory() {
        $stmt = $this->conn->prepare("SELECT * FROM category");
        $stmt->execute();
        $users = $stmt->get_result();
        $stmt->close();
        return $users;
    }

    //----------------- Shop operations --------------------------
    /**
     * Creating new shop
     */
    public function createShop($title, $number_shop, $main_phone, $extra_phone, $site, $desc, $userId, $categoryId, $mallId) {
      // insert query
      $stmt = $this->conn->prepare("INSERT INTO `shop`(`title`, `number_shop`, `main_phone`, `extra_phone`, `site`, `description`, `user_id`, `category_id`, `mall_id`, `c_date`, `m_date`, `view`) VALUES (?,?,?,?,?,?,?,?,?,now(),now(),0)");
      $stmt->bind_param("sssssssss", $title, $number_shop, $main_phone, $extra_phone, $site, $desc, $userId, $categoryId, $mallId);
      $result = $stmt->execute();
      $stmt->close();
      if ($result) {
         // Shop successfully inserted
         return USER_CREATED_SUCCESSFULLY;
      } else {
         // Failed to create shop
         return USER_CREATE_FAILED;
      }
    }

    /**
     * Updating shop
     */
    public function updateShop($shop_id, $title, $number_shop, $main_phone, $extra_phone, $site, $desc, $categoryId, $mallId) {
        $stmt = $this->conn->prepare("UPDATE shop set title = ?, number_shop = ?, main_phone = ?, extra_phone=?, site=?, description=?, category_id=?, mall_id=?, m_date=now() WHERE id = ?");
        $stmt->bind_param("sssssssss", $title, $number_shop, $main_phone, $extra_phone, $site, $desc, $categoryId, $mallId, $shop_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting shop
     */
    public function deleteShop($shop_id) {
        $stmt = $this->conn->prepare("DELETE FROM shop WHERE id = ?");
        $stmt->bind_param("i",$shop_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Fetching shops by user
     */
    public function findShopByUser($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM shop WHERE user_id=?");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $shops = $stmt->get_result();
        $stmt->close();
        return $shops;
    }
    /**
     * Fetching shops by mall
     */
    public function findShopByMall($mall_id) {
        $stmt = $this->conn->prepare("SELECT * FROM shop WHERE mall_id=?");
        $stmt->bind_param("i",$mall_id);
        $stmt->execute();
        $shops = $stmt->get_result();
        $stmt->close();
        return $shops;
    }
    /**
     * Fetching shops by category
     */
    public function findShopByCategory($category_id) {
        $stmt = $this->conn->prepare("SELECT * FROM shop WHERE category_id=?");
        $stmt->bind_param("i",$category_id);
        $stmt->execute();
        $shops = $stmt->get_result();
        $stmt->close();
        return $shops;
    }
    /**
     * Fetching shops by title
     */
    public function findShopByTitle($title) {
        $stmt = $this->conn->prepare("SELECT * FROM shop WHERE title LIKE '%$title%'");
        $stmt->execute();
        $shops = $stmt->get_result();
        $stmt->close();
        return $shops;
    }

    /**
     * Fetching shop by id
     */
    public function findShopById($shop_id) {
        $stmt = $this->conn->prepare("SELECT id, title, number_shop, main_phone, extra_phone, site, description, user_id, category_id, mall_id, c_date, m_date, view FROM shop WHERE id = ?");
        $stmt->bind_param("s", $shop_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            if($num_rows > 0){
                $stmt->bind_result($id, $title, $number_shop, $main_phone, $extra_phone, $site, $description, $user_id, $category_id, $mall_id, $c_date, $m_date, $view);
                $stmt->fetch();
                $shop = array();
                $shop["id"] = $id;
                $shop["title"] = $title;
                $shop["number_shop"] = $number_shop;
                $shop["main_phone"] = $main_phone;
                $shop["extra_phone"] = $extra_phone;
                $shop["site"] = $site;
                $shop["description"] = $description;
                $shop["user_id"] = $user_id;
                $shop["category_id"] = $category_id;
                $shop["mall_id"] = $mall_id;
                $shop["c_date"] = $c_date;
                $shop["m_date"] = $m_date;
                $shop["view"] = $view;
                $stmt->close();
                return $shop;
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }

    //----------------- END Shop operations --------------------------
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
