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
require "phpClasses/class.sales_item.php";
require "phpClasses/class.sale.php";


GUMP::add_validator("is_object", function($field, $input, $param = 'something') {
    return is_object($input[$field]);
});
GUMP::set_error_message("is_object", "Is not an object");


\Slim\Slim::registerAutoloader();

// create new Slim instance
$app = new \Slim\Slim();

//route the requests
//$app->get('/checkAvailability/:start/:end/:spaceId', 'checkAvailability');
//$app->get('/checkAvailability/', 'checkAvailability');
$app->get('/checkAvailabilityByDates/:start/:end', 'checkAvailabilityByDates');
//$app->get('/checkUpdateAvailability/', 'checkUpdateAvailability');
$app->post('/checkAvailability/','checkAvailability');

//customers
$app->get('/customers/', 'getCustomers');
$app->get('/customers/:id', 'getCustomer');
$app->post('/customers/:id', 'updateCustomer');
$app->post('/customers/', 'createCustomer');
$app->put('/something/:id', 'updateSomething');
$app->get('/tmpCreateUser/', 'tmpCreateUser');
$app->post('/customerSearch/', 'customerSearch');
$app->post('/customers/:id', 'updateCustomer');

$app->get('/spaces/', 'getSpaces');
$app->get('/selectGroups/', 'getSelectGroups');
$app->get('/types/', 'getTypes');

//folios
$app->post('/folios/:id', 'getFolio');

//reservations
$app->get('/reservations/:id', 'getReservation');
$app->put('/reservations/:id', 'updateReservation');
$app->get('/reservations/', 'getReservations');
$app->post('/reservations/', 'addReservation');
$app->post('/reservationNotes/:id', 'addReservationNote');

//sales items
$app->get('/sales-items/', 'getSalesItems');

//shifts
$app->get('/userShift/:id', 'getUserShift');
$app->post('/openShift/', 'openShift');

$app->post('/gump/', 'testGump');
$app->post('/login/','login');
$app->post('/logoff/', 'logoff');

//route functions

function updateSomething($id){
  print $id;
}

function addReservation () {
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = json_decode($app->request->getBody(), true);
    $response['params'] = $params;
    //TODO validate user . . . 
    $user = $params['user'];
    //TODO validate reservation . . .
    $reservation = $params['reservation'];
    
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("INSERT INTO reservations (space_id, space_code, checkin, checkout, customer, people, beds, folio, status, notes, history) VALUES (:space_id, :space_code, :checkin, :checkout, :customer, :people, :beds, '0', '1', '[]', '[]')");
    $stmt->bindParam(":space_id", $reservation['space_id'], PDO::PARAM_STR);
    $stmt->bindParam(":space_code", $reservation['space_code'], PDO::PARAM_STR);
    $stmt->bindParam(":checkin", $reservation['checkin'], PDO::PARAM_STR);
    $stmt->bindParam(":checkout", $reservation['checkout'], PDO::PARAM_STR);
    $stmt->bindParam(":customer", $reservation['customer'], PDO::PARAM_INT);
    $stmt->bindParam(":people", $reservation['people'], PDO::PARAM_INT);
    $stmt->bindParam(":beds", $reservation['beds'], PDO::PARAM_INT);
    $response['stmt'] = $stmt;
    $response['execute'] = $stmt->execute();
    $response['errorInfo'] = $stmt->errorInfo();
    $response['insertId']= $pdo->lastInsertId(); 

    print json_encode($response);
}

function addReservationNote( $resId ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['session'] = $_SESSION;
  //TODO validate user
  //TODO validate content
  $iReservation = new Reservation($resId);
  $response['originalReservation'] = (array)$iReservation;
  //add the note
  $params['note']['text'] = nl2br($params['note']['text']);
  $response['addNoteReturn'] = $iReservation->addNote($params['note']);
  $response['iResPreSave'] = (array)$iReservation;
  $response['execute'] = $iReservation->update_to_db();
  $response['iResPostSave'] = (array)$iReservation;
  $jReservation = new Reservation($resId);
  $response['updatedReservation'] = $jReservation;


  print json_encode($response);
}

function checkAvailability( ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['session'] = $_SESSION;
  //TODO validate user . . 
  /*
  //  THIS IS THE CODE FOR CHECKING ON A NEW RESERVATION
  */
  if($params['is_new_res'] == true){
    //get the space_code from the space_id . . .
    $space_code = Spaces::get_subspaces($params['space_id']);
    $response['space_code'] = $space_code;
    
    //build an array of the subspaces . . . 
    $spaces_array = explode(',' , $space_code);
    $response['spaces_array'] = $spaces_array;

    //iterate through those subspaces and check availability . . .
    $pdo = DataConnector::getConnection();
    //convenience: build new variables
    $start = $params['start'];
    $end = $params['end'];
    $space_id = $params['space_id'];
    $is_available = true;
    $queries_by_space = array();
    foreach( $spaces_array as $space ){
      //works, note the comparators are "<" and ">", not "<=" and ">=" because
      //we do allow overlap in sense that one person can checkout on the same
      //day someone checks in
      //  https://stackoverflow.com/questions/325933/determine-whether-two-date-ranges-overlap
      $stmt = $pdo->prepare("SELECT * FROM `reservations` WHERE FIND_IN_SET( :space_id, space_code ) > 0 AND ( :start < `checkout` AND :end > `checkin` )");
      $stmt->bindParam(":start", $start, PDO::PARAM_STR);
      $stmt->bindParam(":end", $end, PDO::PARAM_STR);
      $stmt->bindParam(":space_id", $space, PDO::PARAM_STR);
      $success = $stmt->execute();
      $pdoError = $pdo->errorInfo();
      $response['pdo_error'] = $pdoError;
      $response['success'] = $success;
      $conflicts_array = array();
      //todo? handle the case where the  space_id doesn't exist
      while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
          $iArr = array();
          $iArr['id'] = $obj->id;
          $iArr['start'] = $start;
          $iArr['end'] = $end;
          array_push($conflicts_array, $iArr);
      };
      if(sizeOf($conflicts_array) > 0){
        $is_available = false;
      };
      $queries_by_space[ $space ] = $conflicts_array;
    };
    $response['queries_by_space'] = $queries_by_space;
    $response['is_available'] = $is_available;
    print json_encode($response);
  /*
  //  THIS IS THE CODE FOR CHECKING ON AN EXISTING RESERVATION
  */
  } else {
    //get the space_code from the space_id . . .
    $space_code = Spaces::get_subspaces($params['space_id']);
    $response['space_code'] = $space_code;
    
    //build an array of the subspaces . . . 
    $spaces_array = explode(',' , $space_code);
    $response['spaces_array'] = $spaces_array;

    //iterate through those subspaces and check availability . . .
    $pdo = DataConnector::getConnection();
    //convenience: build new variables
    $start = $params['start'];
    $end = $params['end'];
    $space_id = $params['space_id'];
    $res_id = $params['res_id'];
    $is_available = true;
    $queries_by_space = array();
    foreach( $spaces_array as $space ){
      //works, note the comparators are "<" and ">", not "<=" and ">=" because
      //we do allow overlap in sense that one person can checkout on the same
      //day someone checks in
      //  https://stackoverflow.com/questions/325933/determine-whether-two-date-ranges-overlap
      $stmt = $pdo->prepare("SELECT * FROM `reservations` WHERE FIND_IN_SET( :space_id, space_code ) > 0 AND ( :start < `checkout` AND :end > `checkin` )");
      $stmt->bindParam(":start", $start, PDO::PARAM_STR);
      $stmt->bindParam(":end", $end, PDO::PARAM_STR);
      $stmt->bindParam(":space_id", $space, PDO::PARAM_STR);
      $success = $stmt->execute();
      $pdoError = $pdo->errorInfo();
      $response['pdo_error'] = $pdoError;
      $response['success'] = $success;
      $conflicts_array = array();
      //todo? handle the case where the  space_id doesn't exist
      while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
          $iArr = array();
          $iArr['id'] = $obj->id;
          $iArr['start'] = $start;
          $iArr['end'] = $end;
          //THIS IS THE CRITICAL LOGIC FOR UPDATE
          //exclude if this is the same res id!!!
          if( $obj->id != (string)$res_id ){
            array_push($conflicts_array, $iArr);
          }
      };
      if(sizeOf($conflicts_array) > 0){
        $is_available = false;
      };
      $queries_by_space[ $space ] = $conflicts_array;
    };
    $response['queries_by_space'] = $queries_by_space;
    $response['is_available'] = $is_available;
    print json_encode($response);
  }
}

function checkAvailabilityByDates( $start,$end ){
  $response = array();
  $response['start'] = $start;
  $response['end'] = $end;

  $response['execute'] = Reservations::checkAvailabilityByDates($start, $end);
  print json_encode($response);
};

function checkUpdateAvailability(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = $app->request->params();
  $response['params'] = $app->request->params();
  $subspaces = Spaces::get_subspaces($params['spaceId']);
  $response['subspaces'] = $subspaces;
  $start = $app->request->get('start');
  $end = $app->request->get('end');
  $spaceId = $app->request->get('spaceCode');  
  $resId = $app->request->get('resId');
  $response['query'] = Reservations::checkUpdateAvailability( $start, $end, $subspaces, $resId);
  print json_encode($response);
}

function createCustomer(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO validate user and customer
  $lastName = $params['customer']['lastName'];
  $firstName = $params['customer']['firstName'];
  $address1 = $params['customer']['address1'];
  $address2 = $params['customer']['address2'];
  $city = $params['customer']['city'];
  $region = $params['customer']['region'];
  $postalCode = $params['customer']['postalCode'];
  $country = $params['customer']['country'];
  $phone = $params['customer']['phone'];
  $email = $params['customer']['email'];
  $newId = Customer::addCustomer( $lastName, $firstName, $address1, $address2, $city, $region, $country, $postalCode, $phone, $email );
  $response['newCustomerId'] = $newId;
  $newCustomer = new Customer($newId);
  $response['newCustomer'] = $newCustomer->dumpArray();

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

function getFolio($id){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $folio = new Folio($id);
  $response['folio'] = $folio->to_array();
  //TODO authenticate user

  

  print json_encode($response);
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

function getSalesItems(){
  $response = array();
  $pdo = DataConnector::getConnection();
  //first get the sales groups . . .
  $stmt = $pdo->prepare("SELECT * FROM sales_item_groups ORDER BY `order` ASC");
  $stmt->execute();
  $groupsArr = array();
  while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
    $itemArr = array();
    $itemArr['id'] = $obj->id;
    $itemArr['group_order'] = $obj->group_order;
    $itemArr['title'] = $obj->title;
    $groupsArr[$obj->id] = $itemArr;
  };
  $response['sales_items_groups'] = $groupsArr;
  //now iterate through and get the sales items
  $stmt =$pdo->prepare("SELECT * FROM sales_items ORDER BY group_order ASC");
  $stmt->execute();
  $itemsArr = array();
  while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
    $itArr = array();
    $itArr['id'] = $obj->id;
    $itArr['sales_group'] = $obj->sales_group;
    $itArr['sales_group_order'] = $obj->sales_group_order;
    $itArr['title'] = $obj->title;
    $itArr['is_fixed_price'] = $obj->is_fixed_price;
    $itArr['price'] = $obj->price;
    $itArr['tax_type'] = $obj->tax_type;
    array_push($itemsArr, $itArr);
  };
  $response['sales_items'] = $itemsArr;
  //now iterate through groups, then add items as subarray as appropriate . . . 
  foreach( $groupsArr as $group_id => $group ){
    $groupsArr[$group_id]['groups'] = array();
    foreach( $itemsArr as $sales_item ){
      if($sales_item['sales_group'] == $group_id){
       array_push( $groupsArr[$group_id]['groups'], $sales_item );
      };
    }
  }
  //tmp
  $response['items_by_group'] = $groupsArr;
  print json_encode($response);
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

function getUserShift( $userId ){
  $response = array();
  $response['openShift'] = Shift::getUserOpenShift($userId);
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
    //TODO verify user . . . 
    $iPerson = new Person( $params['user']['userId'] );
    $newShiftId = Shift::open_shift( $params['user']['userId'], $params['startDate']);
    $response['shiftId'] = $newShiftId;
    $newShift = new Shift($newShiftId);
    $response['shift'] = $newShift->to_array();
    print json_encode($response);
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

function updateReservation($id){
  //TODO validate user and data
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['execute'] = Reservation::update_from_params( $params['reservation']['id'], $params['reservation']['space_id'], $params['reservation']['space_code'], $params['reservation']['checkin'], $params['reservation']['checkout'], $params['reservation']['people'], $params['reservation']['beds'], $params['reservation']['folio'], $params['reservation']['status'], json_encode($params['reservation']['history']), json_encode($params['reservation']['notes']), $params['reservation']['customer'] );
  print json_encode($response);
}

$app->run();
