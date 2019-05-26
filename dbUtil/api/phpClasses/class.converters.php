<?php
Class Converters{
    /*  
    *   generate a gpx string from a geoJson string 
    *   @param string $gpx - the gpx string
    *   @param bool $polygonTransform - whether or not to create polygons from closed lines
    *   @return - string - a geoJson FeaturesCollection [?]
    */
    public static function gpxToGeoJson($gpx, $polygonTransform){
        $gpx = new SimpleXMLElement($gpx);
        $finalArray = array();
        //waypoints
        foreach($gpx->wpt as $wpt){
            $iLat = $wpt['lat'];
            $iLng = $wpt['lon'];
            $iName = $wpt->name;
            $iDesc = $wpt->desc;
            $iArr = array();
            $iArr['type'] = "Feature";
            $iArr['geometry'] = array();
            $iArr['geometry']['type'] = "Point";
            $iArr['geometry']['coordinates'] = array();
            array_push($iArr['geometry']['coordinates'],(float)$iLng);
            array_push($iArr['geometry']['coordinates'],(float)$iLat);
            $iArr['properties']['name'] = (string)$wpt->name;
            $iArr['properties']['desc'] = (string)$wpt->desc;
            array_push($finalArray, $iArr);
        }
        //tracks
        foreach($gpx->trk as $trk){
            $iArr = array();
            $iArr['type'] = "Feature";
            $iArr['geometry']['type'] = "LineString";
            $iArr['properties']['name'] = (string)$trk->name;
            $iArr['properties']['desc'] = (string)$trk->desc;
            //geometry
            $iArr['geometry']['coordinates'] = array();
            foreach($trk->trkseg->trkpt as $trkpt){
                //echo"got one <br/>";
                $iLngLat = array();
                array_push($iLngLat, (float)$trkpt['lon']);
                array_push($iLngLat, (float)$trkpt['lat']);
                var_dump($iLngLat);
                array_push($iArr['geometry']['coordinates'],$iLngLat);
            }
            $count = count($iArr['geometry']['coordinates']);
            $isClosed = false;
            if($iArr['geometry']['coordinates'][0] == $iArr['geometry']['coordinates'][$count -1]){
              $isClosed = true;  
            }
            //if it's closed and user wants it, transform it into a polygon
            if($isClosed == true && $polygonTransform == true){
               $iArr['geometry']['type'] = "Polygon";
               $tArr = array();
               array_push($tArr, $iArr['geometry']['coordinates']);
               $iArr['geometry']['coordinates'] = $tArr; 
            }
           array_push($finalArray, $iArr);
        }
        return json_encode($finalArray);
    }
    /*
    *converts geoJson  to gpx preserving "name" and "desc" elements
    */
    public static function json2gpx($json, $closePolygons){
        $jArr = json_decode($json,true);
        $gpx = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?><gpx creator="mytrail.org" version="1.0"></gpx>');
        foreach($jArr['features'] as $iFeature){
            switch( $iFeature['geometry']['type']){
                case "Point":
                    $iPoint = $gpx->addChild('wpt');
                    $iPoint->addAttribute("lat", $iFeature['geometry']['coordinates'][1]);
                    $iPoint->addAttribute("lon", $iFeature['geometry']['coordinates'][0]);   
                    //name
                    $iPoint->addChild('name', $iFeature['properties']['name']);
                    //desc
                    $iPoint->addChild('desc', $iFeature['properties']['desc']);
                break;
                case "LineString":
                    $iPoint = $gpx->addChild('trk');
                    $trkseg = $iPoint->addChild('trkseg');
                    foreach($iFeature['geometry']['coordinates'] as $coord){
                        $iTrkpt = $trkseg->addChild('trkpt');
                        $iTrkpt->addAttribute("lat", $coord[1]);
                        $iTrkpt->addAttribute("lon", $coord[0]);
                    }
                    //name
                    $iPoint->addChild('name', $iFeature['properties']['name']);
                    //desc
                    $iPoint->addChild('desc', $iFeature['properties']['desc']);
                break;
                case "Polygon":
                    $iPoint = $gpx->addChild('trk');
                    $trkseg = $iPoint->addChild('trkseg');
                    foreach($iFeature['geometry']['coordinates'][0] as $coord){
                        $iTrkpt = $trkseg->addChild('trkpt');
                        $iTrkpt->addAttribute("lat", $coord[1]);
                        $iTrkpt->addAttribute("lon", $coord[0]);   
                    }
                    if($closePolygons == true){
                        //add the zero index point so it's a closed linestring
                        $iTrkpt = $trkseg->addChild('trkpt');
                        $iTrkpt->addAttribute("lat", $iFeature['geometry']['coordinates'][0][0][1]);
                        $iTrkpt->addAttribute("lon", $iFeature['geometry']['coordinates'][0][0][0]); 
                    }
                    //name
                    $iPoint->addChild('name', $iFeature['properties']['name']);
                    //desc
                    $iPoint->addChild('desc', $iFeature['properties']['desc']);
                break;
            }
        }
        return $gpx->asXML();
    }
}