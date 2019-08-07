<?php
Class Tax_Type{
  private $id;
  private $tax_title;
  private $tax_rate;
  private $is_current;
  private $display_order;

  public function __construct( $id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tax_types WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    while( $obj = $stmt->fetch( PDO::FETCH_OBJ )){
      $this->id = $obj->id;
      $this->tax_title = $obj->tax_title;
      $this->tax_rate = $obj->tax_rate;
      $this->is_current = $obj->is_current;
      $this->display_order = $obj->display_order;
    }
  }

  public static function add_tax_type( $tax_title, $tax_rate, $is_current, $display_order ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("INSERT INTO tax_types ( tax_title, tax_rate, is_current, display_order ) VALUES ( :tt, :tr, :ic, :do )");
    $stmt->bindParam(":tt", $tax_title);
    $stmt->bindParam(":tr", $tax_rate);
    $stmt->bindParam(":ic", $is_current);
    $stmt->bindParam(":do", $display_order);
    return $stmt->execute(); 
  }

  public static function get_tax_types(){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tax_types ORDER BY is_current DESC, display_order, tax_title");
    $stmt->execute();
    $tax_types = array();
    while( $obj = $stmt->fetchObject() ){
      $i = array();
      $i['id'] = $obj->id;
      $i['tax_title'] = $obj->tax_title;
      $i['tax_rate'] = $obj->tax_rate;
      $i['is_current'] = $obj->is_current;
      $i['display_order'] = $obj->display_order;
      array_push( $tax_types, $i );
    }
    return $tax_types;
  }

  public function to_array(){

  }

  public static function update_from_params( $id, $tax_title, $tax_rate, $is_current, $display_order){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE tax_types SET tax_title = :tt, tax_rate = :tr, is_current = :ic, display_order = :do WHERE id = :id");
    $stmt->bindParam(":tt", $tax_title);
    $stmt->bindParam(":tr", $tax_rate);
    $stmt->bindParam(":ic", $is_current);
    $stmt->bindParam(":do", $display_order);
    $stmt->bindParam(":id", $id);
    return $stmt->execute();
  }

}