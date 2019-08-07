<?php
Class Sale_Type{
  private $id;
  private $title;
  private $is_current;
  private $tax_type;
  //from tax_types
  private $tax_type_title;
  //from tax_types
  private $tax_type_rate;
  private $display_order;

  public function __construct( $sale_type_id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT sale_types.id, sale_types.title, sale_types.is_current, sale_types.tax_type, tax_types.tax_title, tax_types.tax_rate, sale_types.display_order FROM ( sale_types INNER JOIN tax_types ON sale_types.tax_type = tax_types.id ) WHERE sale_types.id = :id");
    $stmt->bindParam(':id', $sale_type_id, PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id= $obj->id;
      $this->title= $obj->title;
      $this->is_current = $obj->is_current;
      $this->tax_type = $obj->tax_type;
      $this->tax_title = $obj->tax_title;
      $this->tax_rate = $obj->tax_rate;
      $this->display_order = $obj->display_order;
    }
  }

  public static function add_sale_type( $title, $tax_type, $is_current, $display_order){
    $pdo = DataConnector::getConnection();
    $stmt=$pdo->prepare("INSERT into sale_types ( title, is_current, tax_type, display_order ) VALUES ( :ti, :ic, :tt, :do)");
    $stmt->bindParam(":ti", $title, PDO::PARAM_STR);
    $stmt->bindParam(":ic", $is_current, PDO::PARAM_INT);
    $stmt->bindParam(":tt", $tax_type, PDO::PARAM_INT);
    $stmt->bindParam(":do", $display_order, PDO::PARAM_INT);
    return $stmt->execute();
  }

  public static function get_sale_types(){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT sale_types.id, sale_types.title, sale_types.is_current, sale_types.tax_type, tax_types.tax_title, tax_types.tax_rate, sale_types.display_order FROM ( sale_types INNER JOIN tax_types ON sale_types.tax_type = tax_types.id ) ORDER BY sale_types.is_current DESC, sale_types.display_order ASC");
    $stmt->execute();
    $sale_types = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $i = array();
      $i['id']= $obj->id;
      $i['title']= $obj->title;
      $i['is_current'] = $obj->is_current;
      $i['tax_type'] = $obj->tax_type;
      $i['tax_title'] = $obj->tax_title;
      $i['tax_rate'] = $obj->tax_rate;
      $i['display_order'] = $obj->display_order;
      array_push($sale_types, $i);
    }
    return $sale_types;
  }

  public function set_display_order( $new_display_order){
    if( is_int( (int)$new_display_order) ){
      $old_display_order = $this->display_order;
      $this->display_order = $new_display_order;
      if( $this->update_to_db() ){
        return true;
      } else {
        $this->display_order = $old_display_order;
        return false;
      }
    } else {
      return false;
    }
  }

  public function set_is_current( $new_is_current){
    $old_is_current = $this->is_current;
    $this->is_current = $new_is_current;
    if( $this->update_to_db() ){
      return true;
    } else {
      $this->is_current = $old_is_current;
      return false;
    }
  }

  public function set_tax_type( $new_tax_type){
    $old_tax_type = $this->tax_type;
    $this->tax_type = $new_tax_type;
    if( $this->update_to_db() ){
      return true;
    } else {
      $this->tax_type = $old_tax_type;
      return false;
    }
  }

  public function set_title( $new_title){
    $old_title = $this->title;
    $this->title = $new_title;
    if( $this->update_to_db() ){
      return true;
    } else {
      $this->title = $old_title;
      return false;
    }
  }

  public function to_array(){
    $i = array();
    $i['id']= $this->id;
    $i['title']= $this->title;
    $i['is_current'] = $this->is_current;
    $i['tax_type'] = $this->tax_type;
    $i['tax_title'] = $this->tax_title;
    $i['tax_rate'] = $this->tax_rate;
    $i['display_order'] = $this->display_order;
    return $i;
  }

  private function update_to_db(){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE sale_types SET title = :ti, is_current = :ic, tax_type = :tt, display_order = :do WHERE id = :id");
    $stmt->bindParam(':ti', $this->title, PDO::PARAM_STR);
    $stmt->bindParam(':ic', $this->is_current, PDO::PARAM_INT);
    $stmt->bindParam(':tt', $this->tax_type, PDO::PARAM_INT);
    $stmt->bindParam(':do', $this->display_order, PDO::PARAM_INT);
    $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
    return $stmt->execute();   
  }

  public static function update_from_params( $id, $title, $is_current, $tax_type, $display_order){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE sale_types SET title = :ti, is_current = :ic, tax_type = :tt, display_order = :do WHERE id = :id");
    $stmt->bindParam(":ti", $title, PDO::PARAM_STR);
    $stmt->bindParam(":ic", $is_current, PDO::PARAM_INT);
    $stmt->bindParam(":tt", $tax_type, PDO::PARAM_INT);
    $stmt->bindParam(":do", $display_order, PDO::PARAM_INT);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

}