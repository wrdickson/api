<html>
<head>
  <title>dbUtil</title>
  <link rel="stylesheet" href="vendor/bootstrap4/bootstrap.min.css">
</head>
<body>
  <div class="container">
    <h4>dbUtil</h4>
    <div class="row">
      <div class="col-6">
        <button id="go" type="button" class="btn btn-success">Go</button>
      </div>
      <div class="col-6">
        col2
      </div>
    </div>
  </div>
  
  <script src="vendor/jquery/jquery.min.js"></script>
  <script>
    $(document).ready(function(){
      console.log("ready");
      var newJson ='{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"LineString","coordinates":[[-109.5536843853668,38.55932855046601],[-109.5440713482574,38.56093932684242],[-109.5360890942291,38.56073798176951]]},"properties":{"name":"someName","desc":"someDesc"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-109.5439855175689,38.55859026589606]},"properties":{"name":"someName","desc":"someDesc"}},{"type":"Feature","geometry":{"type":"Polygon","coordinates":[[[-109.5431272106841,38.55738214751563],[-109.547418745108,38.55281796150101],[-109.5352307873443,38.55375767054552],[-109.5431272106841,38.55738214751563]]]},"properties":{"name":"someName","desc":"someDesc"}}]}';
      console.log("newJson",JSON.parse(newJson));
      
      var obj = {
        geoJson: JSON.parse(newJson)
      };
      $('#go').click( function(){
        save(obj);
      });
      
      function save(obj){
        $.ajax({
          type: 'post',
          url: 'api/layers/3',
          data: JSON.stringify(obj),
          success: function(resp){
            console.log("success", resp);
          },
          error: function(error){
            console.log("error", error);
          }
        });
      };
    });
  </script>
  
</body>
</html>