<?php
class Layer {
    public $id;
    public $owner;
    public $name;
    public $desc;
    public $geoJson;
    public $envelope;
    public $centroid;
    public $properties;
    
    public function __construct ($id) {
        $pdo = DataConnector::getConnection();
        $stmt = $pdo->prepare("SELECT *, AsWKT(`envelope`) AS `envelope`, AsWKT(`centroid`) AS `centroid` FROM `layers` WHERE id = :id");
        $stmt->bindParam(":id",$id,PDO::PARAM_INT);
        $stmt->execute();
        while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
            $this->id = $id;
            $this->owner = $obj->owner;
            $this->name = $obj->name;
            $this->desc = $obj->desc;
            $this->geoJson = json_decode($obj->geoJson, true);
            $this->envelope = $this->wktToJson($obj->envelope);
            $this->centroid = $this->wktToJson($obj->centroid);
            $this->properties = json_decode($obj->properties, true);
        }
    }
    
    public static function createLayer ($owner) {
        $pdo = DataConnector::getConnection();
        $geoJson = '{"features":[],"type":"FeatureCollection"}';
        $name = "layer";
        $description = "description";
        $properties = "{}";
         $stmt = $pdo->prepare("INSERT INTO `layers` (`owner`, `name`, `desc`, `geoJson`, `properties`) VALUES (:owner, :name, :description, :geoJson, :properties)");
        $stmt->bindParam(":owner", $owner, PDO::PARAM_INT);
        $stmt->bindParam(":geoJson", $geoJson, PDO::PARAM_STR);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":description", $description, PDO::PARAM_STR); 
        $stmt->bindParam(":properties", $properties,PDO::PARAM_STR);
        $ii = $stmt->execute();
        $insertId = $pdo->lastInsertId();
        return $insertId;
    }
    
    public function dumpArray() {
        $layerArr = array();
        $layerArr['id'] = $this->id;
        $layerArr['desc'] = $this->desc;
        $layerArr['name'] = $this->name;
        $layerArr['owner'] = $this->owner;
        $layerArr['geoJson'] = $this->geoJson;
        $layerArr['envelope'] = $this->envelope;
        $layerArr['centroid'] = $this->centroid;
        $layerArr['properties'] = $this->properties;
        return $layerArr;
    }
    
    public static function updateLayer($layer) {
            $layerId = (int) $layer['id'];
            $owner = (int) $layer['owner'];
            $name = $layer['name'];
            $desc = $layer['desc'];
            //it's named 'geoJson', but it's a php array in this context
            $geoJson = json_encode($layer['geoJson']);
            //calculate envelope (wkt)
            $envelope = MapUtility::calculateEnvelopeJson2Wkt(json_encode($layer['geoJson']));
            $centroid = MapUtility::calculateCentroidJson2Wkt(json_encode($layer['geoJson']));
            $properties = json_encode($layer['properties']);
            $pdo = DataConnector::getConnection();
            //handle empty layer where envelope and centroid return null
            if($envelope != null && $centroid != null) {
                $stmt = $pdo->prepare("UPDATE `layers` SET `owner` = :owner,`geoJson` = :geoJson, `envelope` = GeomFromText(:env), `centroid` = GeomFromText(:cent), `name` = :name, `desc` = :desc, `properties` = :properties WHERE `id` = :id");
                $stmt->bindParam(":desc", $desc, PDO::PARAM_STR);
                $stmt->bindParam(":name", $name, PDO::PARAM_STR);
                $stmt->bindParam(":owner", $owner, PDO::PARAM_STR);
                $stmt->bindParam(":geoJson", $geoJson, PDO::PARAM_STR);
                $stmt->bindParam(":env", $envelope, PDO::PARAM_STR);
                $stmt->bindParam(":cent", $centroid, PDO::PARAM_STR);
                $stmt->bindParam(":id", $layerId, PDO::PARAM_INT);
                $stmt->bindParam(":properties", $properties, PDO::PARAM_STR);
                $result = $stmt->execute();
                if ($result == true) {
                    return true;
                } else {
                    return false;
                }
            } else {
/*                 //here we put null into centroid and envelope, since it's an empty (or error??) geoJson object
                $stmt = $pdo->prepare("UPDATE layers SET owner = :owner, name = :name, desc = :desc, geoJson = :geoJson, envelope = null, centroid = null, properties = :properties WHERE id = :id");
                $stmt->bindParam(":desc", $desc, PDO::PARAM_STR);
                $stmt->bindParam(":name", $name, PDO::PARAM_STR);
                $stmt->bindParam(":owner", $owner, PDO::PARAM_STR);
                $stmt->bindParam(":geoJson", $geoJson, PDO::PARAM_STR);
                $stmt->bindParam(":id", $layerId, PDO::PARAM_INT);
                $stmt->bindParam(":properties", $properties, PDO::PARAM_STR);
                $result = $stmt->execute();
                if ($result == true) {
                    return true;
                } else {
                    return false;
                } */
                return "error";
            }
    }
    
    public static function updateGeoJson ($geoJson, $id) {
        //validate geoJson
        $j = 1;
        if ($j == 1) {
            $envelope = MapUtility::calculateEnvelopeJson2Wkt($geoJson);
            $centroid = MapUtility::calculateCentroidJson2Wkt($geoJson);
            $pdo = DataConnector::getConnection();
            //handle slashes??
            $stmt = $pdo->prepare("UPDATE layers SET geoJson = :geoJson, envelope = GeomFromText(:env), centroid = GeomFromText(:cent) WHERE id = :id");
            $stmt->bindParam(":geoJson", $geoJson, PDO::PARAM_STR);
            $stmt->bindParam(":env", $envelope, PDO::PARAM_STR);
            $stmt->bindParam(":cent", $centroid, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            if ($result == true) {
                return true;
            } else {
                return false;
            }
        } else {
            return NULL;
        }
    }
    
    public function updateLayerName( $newLayerName ){
        $layerId = $this->id;
        $pdo = DataConnecter::getConnection();
        $stmt = $pdo->prepare("UPDATE layers SET name = :layerName WHERE id = :id");
        $stmt->bindParam(":layerName", $newLayerName, PDO::PARAM_STR);
        $stmt->bindParam(":id", $layerId, PDO::PARAM_INT);
        $result = $stmt->execute();        
        //only update locally if the db update is successful
        if($result == true){
            $this->name = $newLayerName;
            return true;
        }else{
            return false;
        }
    }
    
    private function wktToJson($wkt){
        $geom = geoPHP::load($wkt,'wkt');
        //handle an empty feature collection
        if ($geom == false) {
            return null;
        }
        $json = $geom->out('json');
 
        return $json;
    }    
}