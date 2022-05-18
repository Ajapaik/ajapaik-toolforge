<?php
require_once("common.php");
header("Access-Control-Allow-Origin: *");

$callback="";
if (isset($_GET['callback']) && trim($_GET['callback'])!="" && preg_match("|\A[a-zA-z0-9_]*\z|", $_GET['callback'])) {
   $callback=trim($_GET['callback']);
}

if ($callback!="") {
   header('Content-Type: application/javascript');
}
else
{
   header('Content-Type: application/json');
}

// Example Query
// https://ajapaik.ee/photo/511580/v2/manifest.json
// https://ajapaik.ee/photo/ID/v2/manifest.json


// Flutter app image info parameters
$params=array(
'lat'=>64,
'lon'=>24,
'id'=>511580,
'orderby'=>'date',
'orderdirection'=>'desc',
'limit'=>1000
);

$stringkeys=array("orderby", "orderdirection");
$params=merge_get_parameters($params, $stringkeys);

$url="https://ajapaik.ee/photo/".$params['id']."/v2/manifest.json";
$file=file_get_contents($url);
$json=json_decode($file, true);

$featurelist=array();
foreach($json['sequences'][0]['canvases'] as $k=>$v) {

$metadata=array();
foreach($v['metadata'] as $m) {
	$key=$m["label"]["@value"];
	if ($key=="Source") {
		if (preg_match("|href= ?\"?(.*?)[\" >]|ism", $m["value"], $mm))
		{
			$metadata[$key]=$mm[1];
		}
		else
		{
			die("Error: Source = " . $m["value"]);
		}
	}
	elseif ($key=="Coordinates") {
		$coord=array();
		if (preg_match("|Latitude: ?([0-9.]+)|ism", $m["value"], $mm))
		{
			$coord["Latitude"]=$mm[1];
		}
		else
		{
			die("Error: Latitude = " . $m["value"]);
		}
	
		if (preg_match("|Longitude: ?([0-9.]+)|ism", $m["value"], $mm))
		{
			$coord["Longitude"]=$mm[1];
		}
		else
		{
			die("Error: Longitude = " . $m["value"]);
		}
		$metadata[$key]=$coord;
	}
	else {
		$metadata[$key]=$m["value"];
	}
}
//print_r($metadata);

$label=$v['label']['@value'];
$description="";
if (preg_match("|https://ajapaik.ee/photo/511580/canvas/.*?_([0-9]+)\z|ism", $v['@id'], $mm)) {
	$photo_id=$mm[1];
}

// Geojson properties
$properties=array(
   'id'          =>$photo_id,
   'name'        =>$label,
   'name_orig'   => "ORIG: " . $label,
   'description' =>isset($description) ? "ORIG:" . $description : "",
   'description_orig' =>isset($description) ? "ORIG: " . $description : "",
   'date'        =>isset($metadata['Date']) ? $metadata['Date'] :"",
   'author'      =>isset($metadata['Author']) ? $metadata['Author'] :"",
   'source_url'  =>isset($metadata['Source']) ? $metadata['Source'] :"",
   'source_label'=>isset($metadata['Identifier']) ? $metadata['Identifier'] :"",
   'favorites'   =>"",
   'rephotos'    =>"",
   'thumbnail'  =>$v["thumbnail"]["@id"],
   'iiif_manifest'  => 'https://ajapaik.ee/photo/'.$photo_id.'/v2/manifest.json',
//   'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
//   'licence_label' => 'CC-BY-4.0',
);


// Geojson geometry
$geometry=array();
if (isset($metadata["Coordinates"])) {
   $properties['distance']    = wgs84_distance($metadata["Coordinates"]["Latitude"], $metadata["Coordinates"]['Longitude'], $params['lat'], $params['lon']);
   $geometry=array(
      'type'=>"Point",
      'coordinates'=>array($metadata["Coordinates"]["Longitude"], $metadata["Coordinates"]["Latitude"])
   );
}

// Geojson feature
$feature=array(
   'type'=>"Feature",
   'geometry'=>$geometry,
   'properties'=>$properties

);
$featurelist[$properties["id"]]=$feature;
}

// Do sort
//$sortedfeatureslist = sortfeatures($featurelist, $params['orderby'], $params['orderdirection'], $params['limit']);
 
$outjson=array(array(
  "type"=> "FeatureCollection",
   "features"=> $featurelist
));
 
print(json_encode($outjson,  JSON_PRETTY_PRINT));
die(1);
?>

