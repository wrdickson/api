<?php
session_start();
require "lib/Slim/Slim.php";
require "lib/GUMP/gump.class.php";
require "config/config.php";
require "phpClasses/class.dataconnector.php";
require "phpClasses/class.person.php";
require "phpClasses/class.logger.php";
require "phpClasses/class.customer.php";
require "phpClasses/class.reservations.php";
require "phpClasses/class.validate.php";
require "phpClasses/class.spaces.php";
require "phpClasses/class.reservation.php";
require "phpClasses/class.folio.php";
require "phpClasses/class.shift.php";


GUMP::add_validator("is_object", function($field, $input, $param = 'something') {
    return is_object($input[$field]);
});
GUMP::set_error_message("is_object", "Is not an object");


\Slim\Slim::registerAutoloader();

// create new Slim instance
$app = new \Slim\Slim();

//route the requests
//$app->get('/checkAvailability/:start/:end/:spaceId', 'checkAvailability');
$app->get('/checkAvailability/', 'checkAvailability');
$app->get('/checkAvailabilityByDates/:start/:end', 'checkAvailabilityByDates');
$app->get('/checkUpdateAvailability/', 'checkUpdateAvailability');
$app->get('/customers/', 'getCustomers');
$app->get('/customers/:id', 'getCustomer');
$app->post('/customers/:id', 'updateCustomer');
$app->put('/something/:id', 'updateSomething');
$app->get('/tmpCreateUser/', 'tmpCreateUser');


$app->post('/customerSearch/', 'customerSearch');
$app->post('/customers/:id', 'updateCustomer');

$app->get('/spaces/', 'getSpaces');
$app->get('/selectGroups/', 'getSelectGroups');
$app->get('/types/', 'getTypes');
$app->get('/reservations/:id', 'getReservation');
$app->get('/reservations/', 'getReservations');
$app->post('/gump/', 'testGump');
$app->post('/login/','login');
$app->post('/logoff/', 'logoff');
$app->post('/reservations/', 'addReservation');
$app->post('/openShift/', 'openShift');

$app->get('/folios/', 'getFolios');

//route functions

function updateSomething($id){
  print $id;
}

function addReservation () {
    $app = \Slim\Slim::getInstance();
    $response = array();
    //first get the space_code from space_id
    $response['body'] = $app->request->getBody();
    $params = json_decode($app->request->getBody(), true);
    $response['params'] = json_decode($app->request->getBody(), true);    

    //get the space type
    $space_code = Spaces::get_subspaces($params['space_id']);
    $response['space_code'] = $space_code;
  
    //temp
    $space_type = 2;
     
    
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("INSERT INTO reservations (space_id, space_code, space_type, checkin, checkout, customer, people, beds, folio, status, notes) VALUES (:space_id, :space_code, :space_type, :checkin, :checkout, :customer, :people, :beds, '0', '1', '[]')");
    $stmt->bindParam(":space_id", $params['space_id'], PDO::PARAM_STR);
    $stmt->bindParam(":space_code", $space_code, PDO::PARAM_STR);
    $stmt->bindParam(":space_type", $space_type, PDO::PARAM_INT);
    $stmt->bindParam(":checkin", $params['start'], PDO::PARAM_STR);
    $stmt->bindParam(":checkout", $params['end'], PDO::PARAM_STR);
    $stmt->bindParam(":customer", $params['customer'], PDO::PARAM_INT);
    $stmt->bindParam(":people", $params['people'], PDO::PARAM_INT);
    $stmt->bindParam(":beds", $params['beds'], PDO::PARAM_INT);
    $response['stmt'] = $stmt;
    $response['execute'] = $stmt->execute();
    $response['errorInfo'] = $stmt->errorInfo();
    $response['insertId']= $pdo->lastInsertId(); 

    print json_encode($response);
}

function checkAvailability( ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $response['params'] = $app->request->params();
  
  $params = $app->request->params();
  
  //sanitize


  $gump = new GUMP();
  
  $params = $gump->sanitize($params);
  $response['sParams'] = $params;
  
  $params['test1'] = "hello";
  
  $gump->validation_rules(array(
    'start'     => 'required|date',
    'end'       => 'required|date',
    'spaceCode' => 'required|numeric',
    'test1'     => 'is_object'
  ));

  $gump->filter_rules(array(
    'start'       =>  'trim|sanitize_string',
    'end'         =>  'trim|sanitize_string',       
    'spaceCode'   =>  'trim'
    
  ));
  
  
  

  $validated_data = $gump->run($params);  
  $response['validated_data'] = $validated_data;
  if($validated_data === false) {
    $response['validationError'] = $gump->get_readable_errors(true);
  } else {
    //check to see if the space_id has subspaces
    
  }  
  
  $subspaces = Spaces::get_subspaces($params['spaceCode']);
  $response['subspaces'] = $subspaces;

  $start = $app->request->get('start');
  $end = $app->request->get('end');
  $spaceId = $app->request->get('spaceCode');
  $aQuery = Reservations::checkAvailability( $start, $end, $params['spaceCode']);
  $response['bQuery'] = Reservations::checkConflictsByIdDate( $start, $end, $spaceId );
  $response['query'] = $aQuery;
  // todo, validate beds/ people
  
  print json_encode($response);
}

function checkAvailabilityByDates( $start,$end ){
  $response = array();
  $response['start'] = $start;
  $response['end'] = $end;
  $response['available_space_ids'] = array();
  $response['available_space_ids'] = Reservations::checkAvailabilityByDates($start, $end);
  print json_encode($response);
};

function checkUpdateAvailability(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = $app->request->params();
  $response['params'] = $app->request->params();
  $subspaces = Spaces::get_subspaces($params['spaceCode']);
  $response['subspaces'] = $subspaces;
  $start = $app->request->get('start');
  $end = $app->request->get('end');
  $spaceId = $app->request->get('spaceCode');  
  $resId = $app->request->get('resId');
  $response['query'] = Reservations::checkUpdateAvailability( $start, $end, $subspaces, $resId);
  print json_encode($response);
}

function customerSearch(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $lastName = $params['lastName'];
  $firstName = $params['firstName'];
  $response['customers'] = Customer::searchCustomers($lastName,$firstName);
  print json_encode($response);
};

function getCustomer($id){
  $c = new Customer($id);
  $arr = $c->dumpArray();
  print json_encode($arr);
}

function getCustomers(){
  $cArr = Customer::getCustomers();
  print json_encode($cArr);
}

function getFolios(){
    $folios = array();
    $iFolio = array();
    $iFolio['id'] = 123;
    $iFolio['date'] = "2018-01-27";
    $iFolio['reservation'] = 789;
    array_push($folios, $iFolio);
    print json_encode($folios);
}

function getReservation($id){
    print json_encode(Reservation::getReservation($id));
}

function getReservations () {
    $app = \Slim\Slim::getInstance();
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM reservations");

    $response['execute']= $stmt->execute();
    $arr= array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $cust = new Customer($obj->customer);
        
        $iArr = array();
        $iArr['id'] = $obj->id;
        $iArr['space_id'] = $obj->space_id;
        $iArr['space_code'] = $obj->space_code;
        $iArr['space_type'] = $obj->space_type;
        $iArr['checkin'] = $obj->checkin;
        $iArr['checkout'] = $obj->checkout;
        $iArr['customer'] = $obj->customer;
        $iArr['customer_obj'] = $cust->dumpArray();
        $iArr['people'] = $obj->people;
        $iArr['beds'] = $obj->beds;
        $iArr['folio'] = $obj->folio;
        $iArr['status'] = $obj->status;
        $iArr['history'] = json_decode($obj->history, true);
        $iArr['notes'] = json_decode($obj->notes, true);
        array_push($arr, $iArr);
    };
    $response['reservations'] = $arr;

    print json_encode($arr);
}

function getSelectGroups(){
    $app = \Slim\Slim::getInstance();
    $pdo = DataConnector::getConnection();
    //todo validate user

    $stmt = $pdo->prepare("SELECT * FROM select_groups");
    $success= $stmt->execute();
    $selectGroups= array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $iArr = array();
        $iArr['id'] = $obj->id;
        $iArr['title'] = $obj->title;
        $iArr['order'] = $obj->order;
        $selectGroups[$obj->id] = $iArr;
    };
    //now get the appropriate spaces
    foreach($selectGroups as $group){
      $stmt = $pdo->prepare("SELECT * from spaces WHERE select_group = :groupId");
      $stmt->bindParam(':groupId',$group['id'],PDO::PARAM_INT);
      $success = $stmt->execute();
      $groupsArr = array();
      while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $iArr = array();
        $iArr['space_id'] = $obj->space_id;
        $iArr['text'] = $obj->description;
        $iArr['value'] = $obj->space_id;
        $iArr['description'] = $obj->description;
        array_push($groupsArr, $iArr);
        $selectGroups[$group['id']]['groups'] = array();
        $selectGroups[$group['id']]['groups'] = $groupsArr;
      };
    }
    $response['selectGroups'] = $selectGroups;
    print json_encode($response);  
}

function getSpaces() {
    $app = \Slim\Slim::getInstance();
    $pdo = DataConnector::getConnection();
    //todo validate user

    $stmt = $pdo->prepare("SELECT * FROM spaces ORDER BY show_order ASC");
    $success= $stmt->execute();
    $arr= array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $iArr = array();
        $iArr['space_id'] = $obj->space_id;
        $iArr['space_type'] = $obj->space_type;
        $iArr['description'] = $obj->description;
        $iArr['child_of'] = $obj->child_of;
        //!! IMPORTANT !!
        //casting to (bool) is important when we want to toggle the value in $store
        //if it's passed as 0 or 1, the toggle behavior is erratic
        $iArr['show_subspaces'] = (bool) $obj->show_subspaces;
        $iArr['show_order'] = $obj->show_order;
        $iArr['space_code'] = $obj->space_code;
        $iArr['subspaces'] = $obj->subspaces;
        $iArr['beds'] = $obj->beds;
        $iArr['people'] = $obj->people;
        $iArr['select_group'] = $obj->select_group;
        $iArr['select_order']= $obj->select_order;
        $arr[$obj->space_id] = $iArr;
    };
    $response['spaces'] = $arr;
    //note $app->request->params is for params that were
    //  appended to the get url, not data sent via POST
    $response['id'] = $app->request->params('id');
    $response['key'] = $app->request->params('key');
    $response['session'] = $_SESSION;

    print json_encode($response);
}

function getTypes(){
    $app = \Slim\Slim::getInstance();
    $pdo = DataConnector::getConnection();
    //todo validate user

    $stmt = $pdo->prepare("SELECT * FROM space_types");
    $success= $stmt->execute();
    $arr= array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $iArr = array();
        $iArr['id'] = $obj->id;
        $iArr['title'] = $obj->title;
        $arr[$obj->id] = $iArr;
    };
    $response['space_types'] = $arr;
    //note $app->request->params is for params that were
    //  appended to the get url, not data sent via POST
    $response['id'] = $app->request->params('id');
    $response['key'] = $app->request->params('key');
    $response['session'] = $_SESSION;

    print json_encode($response);
}

function login (){
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = json_decode($app->request->getBody(), true);
    $response['params'] = $params;
    //$response['prePost'] = $_SESSION;
    $response['login'] = Logger::check_login( $params['username'], $params['password'] );
    //$response['postPost'] = $_SESSION;
    print json_encode($response);
}

function logoff(){
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = json_decode($app->request->getBody(), true);
    $response['params'] = $params;
    $response['logoff'] = Logger::logoff( $params['userId'], $params['key'] );
    print json_encode($response);
}

function openShift(){
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = json_decode($app->request->getBody(), true);
    $iPerson = new Person( $params['userId'] );
    $keyVerified = $iPerson->verify_key($params['key']);
    $response['keyVerified'] = $keyVerified;
    $response['shifts'] = Shift::get_shifts_by_user( $params['userId'] );
    
    $response['params'] = $params;
    if($keyVerified == true){
      $response['shiftId'] = Shift::open_shift( $params['userId'], $params['startDate']);
      print json_encode($response);
    }else{
      print json_encode($response);
    }
    
}

function testGump(){
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = $app->request->params();
    $response['params'] = $params;
    $response['post'] = $_POST;

    $is_valid = GUMP::is_valid($_POST, array(
      'firstName' => 'required|alpha_numeric',
      'lastName' => 'required|max_len,3',
      'date' => 'required|date'
    ));


    $response['isValid'] = $is_valid;
    if($is_valid === true) {
      $response['isValid'] = true;
    } else {
      $response['isValid'] = false;
      $response['validationError'] = $is_valid;
    }

    print json_encode($response);
}

function tmpCreateUser(){
  $response = array();
  $password = "schmoe";
  $username = "schmoe";
  $email = "schmoe@whatever.com";
  $permission = "1";
  $response['tmpCreate'] = Logger::createUser($username, $password, $email, $permission);
  print json_encode($response);
}

function updateCustomer($id){
  $app = \Slim\Slim::getInstance();
  $gump = new GUMP();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $params = $gump->sanitize($params);
  $response['params'] = $params;
  $iCustomer = new Customer($id);
  if($params['firstName']){
    $iCustomer->firstName = $params['firstName'];
  };
  if($params['lastName']){
    $iCustomer->lastName = $params['lastName'];
  };
    $iCustomer->address1 = $params['address1'];

    $iCustomer->address2 = $params['address2'];

  if($params['city']){
    $iCustomer->city = $params['city'];
  };
  if($params['region']){
    $iCustomer->region = $params['region'];
  };
  if($params['country']){
    $iCustomer->country = $params['country'];
  };
  if($params['postalCode']){
    $iCustomer->postalCode = $params['postalCode'];
  };
  if($params['phone']){
    $iCustomer->phone = $params['phone'];
  };
  if($params['email']){
    $iCustomer->email = $params['email'];
  };
  $response['execute'] = $iCustomer->update();
  $uCustomer = new Customer($id);
  $response['updatedCustomer'] = $uCustomer->dumpArray();
  
  print json_encode($response);
}

$app->run();
