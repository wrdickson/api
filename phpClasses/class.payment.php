<?php
Class Payment {

  public static function getPaymentsByFolioId( $folio_id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT payments.id, payments.date_posted, payments.amount, payments.payment_type, payment_types.payment_title, payments.posted_by, users.username AS posted_by_username, payments.folio, payments.shift FROM (( payments INNER JOIN payment_types ON payments.payment_type = payment_types.id ) INNER JOIN users ON payments.posted_by = users.id ) WHERE folio = :id");
    $stmt->bindParam(":id", $folio_id);
    $stmt->execute();
    $folios = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ) ){
      $i = array();
      $i['id'] = $obj->id;
      $i['date_posted'] = $obj->date_posted;
      $i['amount'] = $obj->amount;
      $i['payment_type'] = $obj->payment_type;
      $i['payment_title'] = $obj->payment_title;
      $i['posted_by'] = $obj->posted_by;
      $i['posted_by_username'] = $obj->posted_by_username;
      $i['folio'] = $obj->folio;
      $i['shift'] = $obj->shift;
      array_push( $folios, $i );
    }
    return $folios;
  }

  public static function get_payments_by_shift_id( $shift_id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT payments.id, payments.date_posted, payments.amount, payments.payment_type, payment_types.payment_title, payments.posted_by, users.username AS posted_by_username, payments.folio, payments.shift FROM (( payments INNER JOIN payment_types ON payments.payment_type = payment_types.id ) INNER JOIN users ON payments.posted_by = users.id ) WHERE shift = :id ORDER BY payment_type ASC");
    $stmt->bindParam(":id", $shift_id);
    $stmt->execute();
    $folios = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ) ){
      $i = array();
      $i['id'] = $obj->id;
      $i['date_posted'] = $obj->date_posted;
      $i['amount'] = $obj->amount;
      $i['payment_type'] = $obj->payment_type;
      $i['payment_title'] = $obj->payment_title;
      $i['posted_by'] = $obj->posted_by;
      $i['posted_by_username'] = $obj->posted_by_username;
      $i['folio'] = $obj->folio;
      $i['shift'] = $obj->shift;
      array_push( $folios, $i );
    }
    return $folios;
  }

  public static function post_payment( $amount, $payment_type, $posted_by, $folio, $shift ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare( "INSERT INTO payments ( date_posted, amount, payment_type, posted_by, folio, shift ) VALuES ( NOW(), :am, :pt, :pb, :fo, :sh )");
    $stmt->bindParam(":am", $amount);
    $stmt->bindParam(":pt", $payment_type);
    $stmt->bindParam(":pb", $posted_by);
    $stmt->bindParam(":fo", $folio);
    $stmt->bindParam(":sh", $shift);
    $execute = $stmt->execute();
    $error = $stmt->errorInfo();
    return $execute;
  }

}