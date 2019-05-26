<?php

Class MapUtility {
  
  /*
  use the geoPHP class to calculate the centroid of a geoJson collection
  @param string $json - The geoJson string
  @return string  - WKT string of the centroid point
  */
  public static function calculateCentroidJson2Wkt($json){
      $mJson = geoPHP::load($json, "json");
      //handle an empty geojson object 
      if($mJson != true) {
          return "hello";
      };
      $mCentroid = $mJson->centroid();
      $wktCentroid = $mCentroid->out('wkt');
      return $wktCentroid;
  }
  
  /*
  use the geoPHP class to calculate the envelope of a geoJson collection
  @param string $json - The geoJson string
  @return string  - WKT string of the envelope polygon
  */
  public static function calculateEnvelopeJson2Wkt($json){
      $mJson = geoPHP::load($json, "json");
      //handle an empty geojson object
      if ($mJson != true) {
          return null;
      }
      $mEnvelope = $mJson->envelope();
      $wktEnvelope = $mEnvelope->out('wkt');
      return $wktEnvelope;    
  }

  /*
  use the geoPHP class to calculate the envelope of a wkt geometry
  @param string $wkt - The wkt string
  @return string  - WKT string of the envelope polygon
  */
  public static function calculateEnvelopeWkt2Wkt($wkt){
      $mWkt = geoPHP::load($wkt, "wkt");
      $mEnvelope = $mWkt->envelope();
      $wktEnvelope = $mEnvelope->out('wkt');
      return $wktEnvelope;    
  }

}