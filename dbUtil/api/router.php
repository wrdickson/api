<?php
//session_start();
require "lib/Slim/Slim.php";
require "config/config.php";
require "phpClasses/class.layer.php";
require "phpClasses/class.dataconnector.php";
require "phpClasses/class.mapUtility.php";
require "lib/phayes-geoPHP-6855624/geoPHP.inc";

\Slim\Slim::registerAutoloader();

// create new Slim instance
$app = new \Slim\Slim();

//route the requests

    //debug . . .
    $app->get('/items', 'getItems');
    $app->post('/cats', 'addCat');
$app->get('/cats/', 'getCats');
$app->get('/users/', 'getUsers');
$app->get('/layers/:id', 'getLayer');
$app->post('/layers/:id', 'updateLayer');
$app->post('/login','login');

//functions

    //debug . . .
    
    function addCat(){
      $cat = array();
      $cat['id'] = 33;
      $cat['name'] = "Skipper";
      print json_encode($cat);
    }

    function getItems(){
      $items = array();
      $item1 = array();
      $item1['id'] = 1;
      $item1['title'] = "item1";
      $item2 = array();
      $item2['id'] = 2;
      $item2['title'] = "item2";
      array_push($items, $item1);
      array_push($items, $item2);
      print json_encode($items);
    }
    

function getCats(){
  $pdo = DataConnector::getConnection();
  $stmt = $pdo->prepare("SELECT * FROM cats");
  $arr = array();
  $success = $stmt->execute();
  while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
    $iArr = array();
    $iArr['id'] = $obj->id;
    $iArr['name'] = $obj->name;
    $iArr['gender']= $obj->gender;
    $iArr['type'] = $obj->type;
    $iArr['neuteredSpayed'] = $obj->neuteredSpayed;
    $iArr['vaccinated'] = $obj->vaccinated;
    array_push($arr, $iArr);
  }
  $response = array();
  $response['data'] = $arr;
  $response['success'] = $success;
  $response['error_info']= $pdo->errorInfo();
  print json_encode($arr);
}

function getLayer($id){
  $Layer = new Layer($id);
  print json_encode($Layer->dumpArray());
}

function login(){
  print "hello";
}

function getUsers(){
  $pdo = DataConnector::getConnection();
  $stmt = $pdo->prepare("SELECT * FROM users");
  $arr = array();
  $success = $stmt->execute();
  while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
    $iArr = array();
    $iArr['username'] = $obj->username;

    array_push($arr, $iArr);
  }
  $response = array();
  $response['data'] = $arr;
  $response['success'] = $success;
  $response['error_info']= $pdo->errorInfo();
  print json_encode($arr);

}

function updateLayer($id){
  $response = array();
  $app = \Slim\Slim::getInstance();
  $params = json_decode($app->request->getBody(), true);
  $geoJson = $params['geoJson'];
  $layer = new Layer($id);
  $response['update'] = Layer::updateGeoJson(json_encode($geoJson), $id);
  print json_encode($response);
}


$app->run();
