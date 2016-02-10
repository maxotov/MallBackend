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
 *
 */
$app->post('/getGoods',function(){

    $response = array();
    $db = new DbHandler();

    $hostname = php_uname('n');

    // fetching all user tasks
    $result = $db->getGoods();

    $response["error"] = false;
    $response["goods"] = array();

    // looping through result and preparing tasks array
    while ($goods = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $goods["id"];
        $tmp["name"] = $goods["name"];
        $tmp["price"] = $goods["price"];
        $tmp["description"] = $goods["description"];
        $tmp["c_date"]=$goods["c_date"];
        $tmp["m_date"]=$goods["m_date"];
        $tmp["uname"]=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $tmp["photos"]=array();
        $result2=$db->getPhoto($goods["id"]);
        while($photo=$result2->fetch_assoc()){
            $tmp2=array();
            $tmp2["id"]=$photo["id"];
            $tmp2["name"]=$photo["name"];
            $tmp2["description"]=$photo["description"];
            $tmp2["c_date"]=$photo["c_date"];
            $tmp2["m_date"]=$photo["m_date"];
            array_push($tmp["photos"],$tmp2);
        }

        array_push($response["goods"], $tmp);
    }
   echoRespnse(200,$response);
});

/**
 * insert goods with photo
 */
$app->post('/insertGoods',function() use ($app){

    $response = array();

     //reading post param
    $shop_id=$app->request()->post('shop_id');
    $description=$app->request()->post('description');
    $price=$app->request()->post('price');
    $name=$app->request()->post('name');

    $db = new DbHandler();
    $goods_id = $db->insertGoods($name,$price, $description, $shop_id);
    if($goods_id!=null){

        $imgs=array();
        if (!isset($_FILES['uploads'])) {
            $response["files"]=null;
        }else{

            $task=$db->getImagePath($goods_id);

            $mypath="../images/mall_".$task["m_id"]."/shop_".$task["s_id"]."/goods_".$goods_id;

            if (!file_exists($mypath)) {
                mkdir($mypath,0777,TRUE);
            }

            $files = $_FILES['uploads'];
            $cnt = count($files['name']);
            for($i=0;$i<$cnt;$i++){

                if($files['error'][$i]===0){
                    $name=uniqid('img-'.date('Ymd'));
                    $extension=pathinfo($files['name'][$i],PATHINFO_EXTENSION);

                    $photo_id=$db->insertPhoto($files['name'][$i],$mypath.'/'. $name.'.'.$extension,$goods_id);
                    if (move_uploaded_file($files['tmp_name'][$i],$mypath.'/'. $name.'.'.$extension) === true) {
                        $imgs[] = array('url' => $mypath.'/'. $name.'.'.$extension, 'name' => $files['name'][$i]);
                    }
                }
            }
        }

        $response["error"]=false;
        $response["images"]=$imgs;
        $response["message"]="Goods created successfully!";
    }else{
        $response["error"]=true;
        $response["message"]="Oops! An error occurred while creating";
    }

    echoRespnse(201,$response);
});

/**
 * delete goods
 */
$app->post('/deleteGoods',function() use($app){

    $response=array();
    $goods_id=$app->request()->post('goods_id');
    $db=new DbHandler();
    $res=$db->deleteGoods($goods_id);
    if($res!=null){

        $res2=$db->deletePhotoByIdOrGoodsId($goods_id,"all");
        if($res2){
            $response["errorPhoto"]=false;
            $response["messagePhoto"]="Successfully finished";
        } else {
            $response["errorPhoto"]=false;
            $response["messageGoods"]="Oops! An error occurred while deleting (Photo)";
        }

        $response["error"]=false;
        $response["messageGoods"]="Successfully finished";

    }else{
        $response["error"]=true;
        $response["messageGoods"]="Oops! An error occurred while deleting";
    }

    echoRespnse(401,$response);

});

/**
 * update goods
 */
$app->post('/updateGoods',function() use ($app){

    $response = array();

    //reading post param
    $goods_id=$app->request()->post('goods_id');
    $description=$app->request()->post('description');
    $price=$app->request()->post('price');
    $name=$app->request()->post('name');

    $db=new DbHandler();

    $res=$db->updateGoods($goods_id,$name,$price,$description);
    if($res){
        $response["error"]=false;
        $response["message"]="Successfully finished!";
    }else{
        $response["error"]=false;
        $response["message"]="Oops! An error occurred while deleting";
    }
    echoRespnse(400,$response);
});

/**
 * findByGoods
 */
$app->post('/findGoodsById',function() use ($app){

});

$app->post('/findGoodsByShopId',function() use ($app){

});

$app->post('/findGoodsByPrice',function() use ($app){

});

$app->post('/findGoodsByField',function() use ($app){

    $response = array();
    $db = new DbHandler();
    //reading post param
    $method=$app->request()->post('method');
    $name_or_price=$app->request()->post('val');

    if($method=="fieldByName"){
        $result = $db->findGoodsByName($name_or_price);
        $response=findGoodsHelper($result);
    }else if($method=="fieldByPrice"){
        $result = $db->findGoodsByPrice($name_or_price);
        $response=findGoodsHelper($result);
    }else if($method=="fieldById"){
        $result = $db->findGoodsByID($name_or_price,"goods_id");
        $response=findGoodsHelper($result);
    }else if($method=="fieldByShopId"){
        $result = $db->findGoodsByID($name_or_price,"shop_id");
        $response=findGoodsHelper($result);
    }

    echoRespnse(200,$response);
});

function findGoodsHelper($result){

    $db = new DbHandler();

    $response["error"] = false;
    $response["goods"] = array();

    // looping through result and preparing tasks array
    while ($goods = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $goods["id"];
        $tmp["name"] = $goods["name"];
        $tmp["price"] = $goods["price"];
        $tmp["description"] = $goods["description"];
        $tmp["c_date"]=$goods["c_date"];
        $tmp["m_date"]=$goods["m_date"];
        $tmp["uname"]=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $tmp["photos"]=array();

        $result2=$db->getPhoto($goods["id"]);

        while($photo=$result2->fetch_assoc()){
            $tmp2=array();
            $tmp2["id"]=$photo["id"];
            $tmp2["name"]=$photo["name"];
            $tmp2["description"]='http://localhost/MallBackend/MallBackend'.substr($photo["description"], 2, strlen($photo["description"])-2);
            $tmp2["c_date"]=$photo["c_date"];
            $tmp2["m_date"]=$photo["m_date"];
            array_push($tmp["photos"],$tmp2);
        }

        array_push($response["goods"], $tmp);
    }

    return $response;
}

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