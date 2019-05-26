<?php
Class Validate{
  
  public function __construct(){
    
    
  }
  
  public static function is_csv_int( $test ){
      //a simple integer passes
      if(is_int($test) == true){
        return true;
      //now check for csv
      } else {
        $pass = true;
        $exp = explode(',', $test);
        foreach( $exp as $value ){
          if(is_numeric($value) == false){
            $pass = false;
          };
        };
        return $pass;
      };
  }
  
  /*
  *  check that a string is in YYYY-MM-DD format
  */
  public static function is_ymd_dash_date( $test ){
    try{
      $arr = explode('-', $test);
      if(count($arr) == 3){
        if(checkdate( $arr[1], $arr[2], $arr[0] ) == true){
          return true;
        } else {
          return false;
        }     
      } else {
        return false;
      }
    }
    catch( Exception $e) {
      return false;
    }
  }
}