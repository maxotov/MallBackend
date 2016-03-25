<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Aibol
 * Date: 30.11.15
 * Time: 22:25
 * To change this template use File | Settings | File Templates.
 */
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 * ----------- BEGIN REGISTRATION, Authorization ---------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'phone', 'email', 'password', 'device_id'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $phone = $app->request->post('phone');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
            $device_id = $app->request->post('device_id');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $phone, $email, $password, $device_id);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->post('/registerClient', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('device_id'));
    $response = array();

    // reading post params
    $device_id = $app->request->post('device_id');

    $db = new DbHandler();
    $res = $db->createBasicUser($device_id);

    if ($res == USER_CREATED_SUCCESSFULLY) {
        $response["error"] = false;
        $response["message"] = "You are successfully registered";
    } else if ($res == USER_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
    } else if($res == DEVICE_ALREADY_EXISTED){
        $response["error"] = true;
        $response["message"] = "Sorry, this device already existed";
    }
    // echo json response
    echoRespnse(201, $response);
});
/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    //$response["error"] = false;
                    $response['id'] = $user['id'];
                    $response['name'] = $user['name'];
                    $response['phone'] = $user['phone'];
                    $response['email'] = $user['email'];
                    $response['c_date'] = $user['c_date'];
                    $response['m_date'] = $user['m_date'];
                    $response['apiKey'] = $user['api_key'];
                    $response['device_id'] = $user['device_id'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });
//----------- END REGISTRATION, Authorization ----------------------------------------------------------
//----------- BEGIN USER OPERATIONS---------------------------------------------------------------------
/**
 * getUser
 * url - /login
 * method - POST
 * params - email, password
 */
$app->get('/getUserById', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('id'));

    // reading post params
    $userId = $app->request()->get('id');
    $response = array();

    $db = new DbHandler();
        // get the user by email
        $user = $db->getUserById($userId);

        if ($user != NULL) {
            $response["error"] = false;
            $response['id'] = $user['id'];
            $response['name'] = $user['name'];
            $response['phone'] = $user['phone'];
            $response['email'] = $user['email'];
            $response['c_date'] = $user['c_date'];
            $response['m_date'] = $user['m_date'];
            $response['apiKey'] = $user['api_key'];
            $response['device_id'] = $user['device_id'];
        } else {
            // unknown error occurred
            $response['error'] = true;
            $response['message'] = "An error occurred. No such user by primary key ".$userId;
        }
    echoRespnse(200, $response);
});

$app->get('/getUserByEmail', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('email'));

    // reading post params
    $email = $app->request()->get('email');
    $response = array();

    $db = new DbHandler();
    // get the user by email
    $user = $db->getUserByEmail($email);

    if ($user != NULL) {
        $response["error"] = false;
        $response['id'] = $user['id'];
        $response['name'] = $user['name'];
        $response['phone'] = $user['phone'];
        $response['email'] = $user['email'];
        $response['c_date'] = $user['c_date'];
        $response['m_date'] = $user['m_date'];
        $response['apiKey'] = $user['api_key'];
        $response['device_id'] = $user['device_id'];
    } else {
        // unknown error occurred
        $response['error'] = true;
        $response['message'] = "An error occurred. No such user by email ".$email;
    }
    echoRespnse(200, $response);
});

$app->get('/getUserByDevice', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('device'));

    // reading post params
    $device = $app->request()->get('device');
    $response = array();

    $db = new DbHandler();
    // get the user by email
    $user = $db->getUserByDevice($device);

    if ($user != NULL) {
        $response["error"] = false;
        $response['id'] = $user['id'];
        $response['name'] = $user['name'];
        $response['phone'] = $user['phone'];
        $response['email'] = $user['email'];
        $response['c_date'] = $user['c_date'];
        $response['m_date'] = $user['m_date'];
        $response['apiKey'] = $user['api_key'];
        $response['device_id'] = $user['device_id'];
    } else {
        // unknown error occurred
        $response['error'] = true;
        $response['message'] = "An error occurred. No such user by device ".$device;
    }
    echoRespnse(200, $response);
});

$app->get('/users', function() {
    $response = array();
    $db = new DbHandler();
    // fetching all user
    $result = $db->getAllUser();

    $response["error"] = false;
    $response["users"] = array();

    // looping through result and preparing tasks array
    while ($user = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $user["id"];
        $tmp["name"] = $user["name"];
        $tmp["phone"] = $user["phone"];
        $tmp["email"] = $user["email"];
        $tmp["c_date"] = $user["c_date"];
        $tmp["m_date"] = $user["m_date"];
        $tmp["api_key"] = $user["api_key"];
        $tmp["device_id"] = $user["device_id"];
        array_push($response["users"], $tmp);
    }

    echoRespnse(200, $response);
});

$app->delete('/users/:id', function($user_id) use($app) {
    $db = new DbHandler();
    $response = array();
    $result = $db->deleteUserById($user_id);
    if ($result) {
        // task deleted successfully
        $response["error"] = false;
        $response["message"] = "User deleted succesfully";
    } else {
        // task failed to delete
        $response["error"] = true;
        $response["message"] = "User failed to delete. Please try again!";
    }
    echoRespnse(200, $response);
});

//----------- END USER OPERATIONS---------------------------------------------------------------------
//----------- BEGIN MALL OPERATIONS---------------------------------------------------------------------
$app->get('/malls', function() {
    $response = array();
    $db = new DbHandler();
    // fetching all mall
    $result = $db->getAllMall();
    $response = array();
    // looping through result and preparing malls array
    while ($user = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $user["id"];
        $tmp["name"] = $user["name"];
        $tmp["address"] = $user["address"];
        $tmp["lat"] = $user["lat"];
        $tmp["lng"] = $user["lng"];
        $tmp["c_date"] = $user["c_date"];
        $tmp["m_date"] = $user["m_date"];
        array_push($response, $tmp);
    }
    echoRespnse(200, $response);
});
//----------- END MALL OPERATIONS---------------------------------------------------------------------

//----------- BEGIN CATEGORY OPERATIONS---------------------------------------------------------------------
$app->get('/categories', function() {
    $response = array();
    $db = new DbHandler();
    // fetching all mall
    $result = $db->getAllCategory();
    $response["error"] = false;
    $response["cats"] = array();
    // looping through result and preparing malls array
    while ($cat = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $cat["id"];
        $tmp["title"] = $cat["title"];
        $tmp["c_date"] = $cat["c_date"];
        $tmp["m_date"] = $cat["m_date"];
        array_push($response["cats"], $tmp);
    }
    echoRespnse(200, $response);
});
//----------- END CATEGORY OPERATIONS---------------------------------------------------------------------

//----------- BEGIN SHOP OPERATIONS---------------------------------------------------------------------

/**
 * Shop Create
 */
$app->post('/createShop', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('title', 'main_phone', 'description', 'user_id', 'category_id', 'mall_id'));

    $response = array();

    // reading post params
    $title = $app->request->post('title');
    $number_shop = $app->request->post('number_shop');
    $main_phone = $app->request->post('main_phone');
    $extra_phone = $app->request->post('extra_phone');
    $site = $app->request->post('site');
    $description = $app->request->post('description');
    $user_id = $app->request->post('user_id');
    $category_id = $app->request->post('category_id');
    $mall_id = $app->request->post('mall_id');

    $db = new DbHandler();
    $res = $db->createShop($title, $number_shop, $main_phone, $extra_phone, $site, $description, $user_id, $category_id, $mall_id);

    if ($res == USER_CREATED_SUCCESSFULLY) {
        $response["error"] = false;
        $response["message"] = "Shop is successfully created";
    } else if ($res == USER_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while creating";
    }
    // echo json response
    echoRespnse(201, $response);
});
/**
 * Shop Update
 */
$app->post('/updateShop', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('shop_id', 'title', 'main_phone', 'description', 'category_id', 'mall_id'));

    $response = array();

    // reading post params
    $shop_id = $app->request->post('shop_id');
    $title = $app->request->post('title');
    $number_shop = $app->request->post('number_shop');
    $main_phone = $app->request->post('main_phone');
    $extra_phone = $app->request->post('extra_phone');
    $site = $app->request->post('site');
    $description = $app->request->post('description');
    $category_id = $app->request->post('category_id');
    $mall_id = $app->request->post('mall_id');

    $db = new DbHandler();
    $res = $db->updateShop($shop_id, $title, $number_shop, $main_phone, $extra_phone, $site, $description, $category_id, $mall_id);

    if ($res) {
        $response["error"] = false;
        $response["message"] = "Shop is successfully updated";
    } else {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while updating";
    }
    // echo json response
    echoRespnse(201, $response);
});
/*
 * Delete shop
 */
$app->delete('/shops/:id', function($shop_id) use($app) {
    $db = new DbHandler();
    $response = array();
    $result = $db->deleteShop($shop_id);
    if ($result) {
        $response["error"] = false;
        $response["message"] = "Shop deleted succesfully";
    } else {
        $response["error"] = true;
        $response["message"] = "Shop failed to delete. Please try again!";
    }
    echoRespnse(200, $response);
});
/**
 * get shop by id
 */
$app->get('/getShopById', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('shop_id'));
    // reading post params
    $shopId = $app->request()->get('shop_id');
    $response = array();
    $db = new DbHandler();
    $shop = $db->findShopById($shopId);
    if ($shop != NULL) {
        $response["error"] = false;
        $response['id'] = $shop['id'];
        $response['title'] = $shop['title'];
        $response['number_shop'] = $shop['number_shop'];
        $response['main_phone'] = $shop['main_phone'];
        $response['extra_phone'] = $shop['extra_phone'];
        $response['site'] = $shop['site'];
        $response['description'] = $shop['description'];
        $response['user_id'] = $shop['user_id'];
        $response['category_id'] = $shop['category_id'];
        $response['mall_id'] = $shop['mall_id'];
        $response['c_date'] = $shop['c_date'];
        $response['m_date'] = $shop['m_date'];
        $response['view'] = $shop['view'];
    } else {
        // unknown error occurred
        $response['error'] = true;
        $response['message'] = "An error occurred. No such shop by primary key ".$shopId;
    }
    echoRespnse(200, $response);
});
/*
 * find shops by user
 */
$app->get('/findShopByUser', function() use ($app) {
    verifyRequiredParams(array('user_id'));
    // reading post params
    $userId = $app->request()->get('user_id');
    $response = array();
    $db = new DbHandler();
    $result = $db->findShopByUser($userId);
    $response = array();
    // looping through result and preparing malls array
    while ($shop = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['id'] = $shop['id'];
        $tmp['title'] = $shop['title'];
        $tmp['number_shop'] = $shop['number_shop'];
        $tmp['main_phone'] = $shop['main_phone'];
        $tmp['extra_phone'] = $shop['extra_phone'];
        $tmp['site'] = $shop['site'];
        $tmp['description'] = $shop['description'];
        $tmp['user_id'] = $shop['user_id'];
        $tmp['category_id'] = $shop['category_id'];
        $tmp['mall_id'] = $shop['mall_id'];
        $tmp['c_date'] = $shop['c_date'];
        $tmp['m_date'] = $shop['m_date'];
        $tmp['view'] = $shop['view'];
        array_push($response, $tmp);
    }
    echoRespnse(200, $response);
});
/*
 * find shops by mall
 */
$app->get('/findShopByMall', function() use ($app) {
    verifyRequiredParams(array('mall_id'));
    // reading post params
    $mallId = $app->request()->get('mall_id');
    $response = array();
    $db = new DbHandler();
    $result = $db->findShopByMall($mallId);
    //$response["error"] = false;
    $response = array();
    // looping through result and preparing malls array
    while ($shop = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['id'] = $shop['id'];
        $tmp['title'] = $shop['title'];
        $tmp['number_shop'] = $shop['number_shop'];
        $tmp['main_phone'] = $shop['main_phone'];
        $tmp['extra_phone'] = $shop['extra_phone'];
        $tmp['site'] = $shop['site'];
        $tmp['description'] = $shop['description'];
        $tmp['user_id'] = $shop['user_id'];
        $tmp['category_id'] = $shop['category_id'];
        $tmp['mall_id'] = $shop['mall_id'];
        $tmp['c_date'] = $shop['c_date'];
        $tmp['m_date'] = $shop['m_date'];
        $tmp['view'] = $shop['view'];
        array_push($response, $tmp);
    }
    echoRespnse(200, $response);
});
/*
 * find shops by category
 */
$app->get('/findShopByCategory', function() use ($app) {
    verifyRequiredParams(array('category_id'));
    // reading post params
    $category_id = $app->request()->get('category_id');
    $response = array();
    $db = new DbHandler();
    $result = $db->findShopByCategory($category_id);
    $response["error"] = false;
    $response["shops"] = array();
    // looping through result and preparing malls array
    while ($shop = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['id'] = $shop['id'];
        $tmp['title'] = $shop['title'];
        $tmp['number_shop'] = $shop['number_shop'];
        $tmp['main_phone'] = $shop['main_phone'];
        $tmp['extra_phone'] = $shop['extra_phone'];
        $tmp['site'] = $shop['site'];
        $tmp['description'] = $shop['description'];
        $tmp['user_id'] = $shop['user_id'];
        $tmp['category_id'] = $shop['category_id'];
        $tmp['mall_id'] = $shop['mall_id'];
        $tmp['c_date'] = $shop['c_date'];
        $tmp['m_date'] = $shop['m_date'];
        $tmp['view'] = $shop['view'];
        array_push($response["shops"], $tmp);
    }
    echoRespnse(200, $response);
});
/*
 * find shops by title
 */
$app->get('/findShopByTitle', function() use ($app) {
    verifyRequiredParams(array('title'));
    // reading post params
    $title = $app->request()->get('title');
    $response = array();
    $db = new DbHandler();
    $result = $db->findShopByTitle($title);
    $response["error"] = false;
    $response["shops"] = array();
    // looping through result and preparing malls array
    while ($shop = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['id'] = $shop['id'];
        $tmp['title'] = $shop['title'];
        $tmp['number_shop'] = $shop['number_shop'];
        $tmp['main_phone'] = $shop['main_phone'];
        $tmp['extra_phone'] = $shop['extra_phone'];
        $tmp['site'] = $shop['site'];
        $tmp['description'] = $shop['description'];
        $tmp['user_id'] = $shop['user_id'];
        $tmp['category_id'] = $shop['category_id'];
        $tmp['mall_id'] = $shop['mall_id'];
        $tmp['c_date'] = $shop['c_date'];
        $tmp['m_date'] = $shop['m_date'];
        $tmp['view'] = $shop['view'];
        array_push($response["shops"], $tmp);
    }
    echoRespnse(200, $response);
});
//----------- END SHOP OPERATIONS---------------------------------------------------------------------

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>