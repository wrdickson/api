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
require "phpClasses/class.reservation_history.php";
require "phpClasses/class.sale_type.php";
require "phpClasses/class.tax_type.php";
require "phpClasses/class.sales_group.php";
require "phpClasses/class.payment.php";
require "phpClasses/class.payment_type.php";

//set the server timezone
date_default_timezone_set( DEFAULT_TIMEZONE );

GUMP::add_validator("is_object", function($field, $input, $param = 'something') {
    return is_object($input[$field]);
});
GUMP::set_error_message("is_object", "Is not an object");


\Slim\Slim::registerAutoloader();

// create new Slim instance
$app = new \Slim\Slim();

//route the requests
$app->get('/checkAvailabilityByDates/:start/:end', 'checkAvailabilityByDates');
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

//payments
$app->post('/payment/', 'post_payment');

//payment types
$app->get('/payment-types/', 'get_payment_types');

//reservations
$app->get('/reservations/:id', 'getReservation');
$app->put('/reservations/:id', 'updateReservation');
$app->get('/reservations/', 'getReservations');
$app->post('/reservations/', 'addReservation');
$app->post('/reservationNotes/:id', 'addReservationNote');
$app->put('/reservation/checkin/:id', 'checkinReservation');
$app->put('/reservation/checkout/:id', 'checkoutReservation');

//sales
$app->get('/sales/:id', 'getSalesByFolioId');
//:id is the folio id here
$app->post('/sale/:id', 'postSale');

//sales groups
$app->get('/sales-groups/', 'get_sales_groups');


//sales items
$app->get('/sales-items/', 'getSalesItems');
$app->put('/sales-items/:id', 'update_sales_item');
$app->post('/sales-items/', 'add_sales_item');

//sale types
$app->get('/sale-types/', 'getSaleTypes');
$app->put('/sale-types/:id', 'update_sale_type');
$app->post('/sale-types/', 'add_sale_type');

//shifts
$app->get('/userShift/:id', 'getUserShift');
$app->post('/openShift/', 'openShift');
$app->put('/shifts/close/:id', 'close_shift');
$app->post('/shift-data/:id', 'get_shift_data');
$app->post('/shift-reopen-options/', 'reopen_shift_options');
$app->post('/shift-reopen/', 'reopen_shift');

$app->post('/gump/', 'testGump');
$app->post('/login/','login');
$app->post('/logoff/', 'logoff');

//tax types
$app->get('/tax-types/', 'get_tax_types');
$app->put('/tax-types/:id', 'update_tax_type');
$app->post('/tax-types/', 'add_tax_type');


//debug temp
$app->post('/headerTest/', 'headerTest');

//route functions

function headerTest(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $something = $app->request->headers->get('X-Something-Something');
  $response['someting'] = $something;
  $response['params'] = $params;
  print json_encode($response);
}

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

    //TODO make this a transaction
    //create the reservations
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


    //$pdo = null; 

    //create a folio
    //$pdo = DataConnector::getConnection();
    $stmt2 = $pdo->prepare('INSERT INTO folios ( customer, reservation ) VALUES (:custId, :resId)');
    $stmt2->bindParam(':custId', $reservation['customer'], PDO::PARAM_INT);
    $stmt2->bindParam(':resId', $response['insertId']);
    $response['folioExecute'] = $stmt2->execute();
    $response['folioId'] = $pdo->lastInsertId();
    $response['folioError'] = $stmt2->errorInfo();
    //$pdo = null;

    //now update the reservation with the folio #
    //$pdo = DataConnector::getConnection();
    $stmt3 = $pdo->prepare("UPDATE reservations SET folio = :folioId WHERE id = :resId");
    $stmt3->bindParam(':folioId', $response['folioId'], PDO::PARAM_INT);
    $stmt3->bindParam(':resId', $response['insertId'], PDO::PARAM_INT);
    $response['updateResWithFolio'] = $stmt3->execute();

    //create a record in reshistory
    $stmt4 = $pdo->prepare("INSERT INTO reshistory ( res_id, history ) VALUES ( :res_id, '[]' )");
    $stmt4->bindParam(':res_id', $response['insertId'], PDO::PARAM_INT);
    $response['historyRecordCreated'] = $stmt4->execute();
    
    //instantiate the reservation
    $iRes = new Reservation( $response['insertId'] );
    $response['historyUpdated'] = $iRes->add_history('Reservation Created', $params['user']['userId'], $params['user']['username'] );

    //create a record for reshistory
    $iResHistory = new Reservation_History( $response['insertId'] );
    $response['resHist init'] = $iResHistory->to_array();
    //update history with this reservation
    $iResHistory->add_history_snapshot( $iRes->to_array(), $params['user']['userId'], $params['user']['username'] );
    $response['resHist after add'] = $iResHistory->to_array();
    

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

function add_sale_type(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO validate newSaleType
  //TODO authenticate user
  $response['execute'] = Sale_Type::add_sale_type( $params['newSaleType']['title'], $params['newSaleType']['tax_type'], $params['newSaleType']['is_current'], $params['newSaleType']['display_order']);
  //return an array of new sale types 
  $response['sale_types'] = Sale_Type::get_sale_types();
  print json_encode($response);
}

function add_sales_item(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['execute'] = Sales_Item::add_sales_item( $params['sales_item']['sales_group'], $params['sales_item']['group_order'], $params['sales_item']['sales_item_code'], $params['sales_item']['sales_item_title'], $params['sales_item']['is_fixed_price'], $params['sales_item']['price'], $params['sales_item']['tax_type'] );
  $response['sales_items'] = Sales_Item::get_sales_items();
  print json_encode( $response );
}

function add_tax_type(){
  //TODO authenticate user and validate params
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['execute'] = Tax_Type::add_tax_type( $params['tax_type']['tax_title'], $params['tax_type']['tax_rate'], $params['tax_type']['is_current'], $params['tax_type']['display_order']);
  $response['tax_types'] = Tax_Type::get_tax_types();
  print json_encode( $response );
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

function checkinReservation(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO authenticate user

  $iReservation = new Reservation( $params['resId'] );
  $response['success'] = $iReservation->checkin();
  print json_encode($response);
}

function checkoutReservation(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO authenticate user

  $iReservation = new Reservation( $params['resId'] );
  $response['success'] = $iReservation->checkout();
  print json_encode($response);
}

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

function close_shift( $shift_id ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $shift = new Shift( $shift_id );
  $response['execute'] = $shift->close_shift();

  print json_encode( $response );
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

function get_payment_types(){
  $response = array();
  $response['payment_types'] = Payment_Type::get_payment_types();
  print json_encode( $response );
}

function getReservation($id){
    $iReservation = new Reservation( $id );

    print json_encode( $iReservation->to_array() );
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

function getSalesByFolioId( $id ){
  
  $response = array();
  $response['folioId'] = $id;
  $response['sales'] = Sale::loadSalesByFolioId($id);

  print json_encode($response);
}

function get_sales_groups(){
  $response = array();
  $response['sales_groups'] = Sales_Group::get_sales_groups();
  print json_encode( $response );
}

function getSalesItems(){
  $response = array();
  $response['sales_items'] = Sales_Item::get_sales_items();
  print json_encode( $response );
}

function getSaleTypes(){
  $response = array();
  $response['sale_types'] = Sale_Type::get_sale_types();
  print json_encode( $response );
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

function get_shift_data( $shift_id ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $shift = new Shift($shift_id);
  $response['shift'] = $shift->to_array();
  $response['sales'] = $shift->get_sales();
  $response['payments'] = $shift->get_payments();
  print json_encode( $response );  
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

function get_tax_types(){
  $response = array();
  $response['tax_types'] = Tax_Type::get_tax_types();
  print json_encode( $response );
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
  $response['params'] = $params;
  //TODO verify user . . . 
  $iPerson = new Person( $params['user']['userId'] );
  $newShiftId = Shift::open_shift( $params['user']['userId']);
  $response['shiftId'] = $newShiftId;
  $newShift = new Shift($newShiftId);
  $response['shift'] = $newShift->to_array();
  print json_encode($response);
}

function post_payment(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['execute'] = Payment::post_payment( $params['payment_obj']['amount'], $params['payment_obj']['payment_type'], $params['payment_obj']['posted_by'], $params['payment_obj']['folio'], $params['payment_obj']['shift'] );
  print json_encode( $response);
}

function postSale($folioId){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO authenticate user 
  //TODO authenticate sale
  $post_success = Sale::post_sale( $params['sale_obj']['sales_item'], $params['sale_obj']['quantity'], $params['sale_obj']['net'], $params['sale_obj']['tax'], $params['sale_obj']['total'], $params['sale_obj']['sold_by'], $folioId, $params['sale_obj']['shift'], $params['sale_obj']['notes'] );
  $response['postSuccess'] = $post_success;
  print json_encode( $response );
}

function reopen_shift(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //  TODO authenticate user
  $shift = new Shift($params['shift_id']);
  $response['reopenShift'] = $shift->reopen_shift();
  $response['shift'] = $shift->to_array();

  print json_encode($response);
}

function reopen_shift_options(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //  TODO authenticate user

  //make sure the user doesn't have any open shifts
  $userId = $params['user']['userId'];
  // this will report the number of open shifts
  // 0 means they can reopen
  // 1 means they have an open shift and can NOT reopen
  // longer than 1 means we are in deep, deep trouble
  $open_shift_quantity= Shift::check_open_shifts_by_user( $userId );
  $response['check1'] = $open_shift_quantity;
  $response['userId'] = $params['user']['userId'];
  
  if( $open_shift_quantity == 0 ){
    //get the shifts that could be reopened
    $response['closed_shifts'] = Shift::get_closed_shifts_by_user_id( $params['user']['userId'] );

  } else {
    //  return an error
    $response['closed_shifts'] = Shift::get_closed_shifts_by_user_id( $params['user']['userId'] );
  };


  print json_encode( $response );
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

  //now add a history note
  $iReservation = new Reservation( $params['reservation']['id'] );
  $hText = "Reservation changed.";
  $response['historyUpdated'] = $iReservation->add_history( $hText, $params['user']['userId'], $params['user']['username'] );

  //instantiate reshistory
  $iResHistory = new Reservation_History( $id );
  //update history with this reservation
  $iResHistory->add_history_snapshot( $iReservation->to_array(), $params['user']['userId'], $params['user']['username'] );
  

  print json_encode($response);
}

function update_sale_type( $sale_type_id ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  //TODO authenticate user
  //update db . . .
  $response['update'] = Sale_Type::update_from_params( $sale_type_id, $params['saleType']['title'], $params['saleType']['is_current'], $params['saleType']['tax_type'], $params['saleType']['display_order']);
  //return an array of new sale types 
  $response['sale_types'] = Sale_Type::get_sale_types();
  print json_encode( $response );
}

function update_sales_item( $id ){
  //TODO authenticate user and validate params
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['execute'] = Sales_Item::update_from_params($params['sales_item']['id'], $params['sales_item']['sales_group'], $params['sales_item']['group_order'], $params['sales_item']['sales_item_code'], $params['sales_item']['sales_item_title'], $params['sales_item']['is_fixed_price'], $params['sales_item']['price'], $params['sales_item']['tax_type']);
  $response['sales_items'] = Sales_Item::get_sales_items(); 

  print json_encode( $response );
}

function update_tax_type( $tax_type_id ){
  //TODO authenticate user and validate params
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['update'] = Tax_Type::update_from_params( $tax_type_id, $params['tax_type']['tax_title'], $params['tax_type']['tax_rate'], $params['tax_type']['is_current'], $params['tax_type']['display_order']);
  $response['tax_types'] = Tax_Type::get_tax_types();
  print json_encode( $response );
}

$app->run();
