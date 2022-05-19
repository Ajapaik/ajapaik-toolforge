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
//https://ajapaik.ee/api/v1/album/nearest/?latitude=49.84189&longitude=24.0315&limit=100"

// Flutter app parameters
$params=array(
'latitude'=>49.8418,
'longitude'=>24.0315,
'limit'=>100,
'search'=>'',
'orderby'=>'distance',
'orderdirection'=>'desc'
);

$stringkeys=array("search", "orderby", "orderdirection");
$params=merge_get_parameters($params, $stringkeys);

// Fetch data
if ($params["search"]=="") {
//	$file=file_get_contents("https://ajapaik.ee/api/v1/album/nearest/?latitude=" . $params['latitude'] ."&longitude=" . $params['longitude'] ."&limit=" . $params['limit']);
	$file=file_get_contents("https://staging.ajapaik.ee/api/v1/album/nearest/?latitude=" . $params['latitude'] ."&longitude=" . $params['longitude'] ."&limit=" . $params['limit']);
	$file=file_get_contents("https://ajapaik.ee/api/v1/album/nearest/?latitude=" . $params['latitude'] ."&longitude=" . $params['longitude'] ."&limit=" . $params['limit']);
}
else {
//	$url="https://ajapaik.ee/api/v1/photos/search/?latitude=" . $params['latitude'] ."&longitude=" . $params['longitude'] ."&query=" .urlencode($params['search']) ."&limit=" . $params['limit'];
	$url="https://ajapaik.ee/api/v1/photos/search/?latitude=" . $params['latitude'] ."&longitude=" . $params['longitude'] ."&query=" .urlencode($params['search']) ."&limit=" . $params['limit'];
	$file=file_get_contents($url);
}
$json=json_decode($file, true);

$featurelist=array();
foreach($json['photos'] as $k=>$v) {

// Geojson properties
$properties=array(
   'id'          =>$v['id'],
   'name'        =>$v['title'],
   'name_orig'        => "ORIG: " . $v['title'],
   'description' =>isset($description) ? "ORIG:" . $description : "",
   'description_orig' =>isset($description) ? "ORIG: " . $description : "",
   'date'        =>$v['date'],
   'author'      =>$v['author'],
   'source_url'  =>$v["source"]["url"],
   'source_label'=>$v["source"]["name"],
   'favorites'   =>$v['favorited'],
   'rephotos'    =>count($v['rephotos']),
   'thumbnail'  =>str_replace("[DIM]", "400", $v['image']),
   'iiif_manifest'  => 'https://ajapaik.ee/photo/'.$v['id'].'/v2/manifest.json',
//   'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
//   'licence_label' => 'CC-BY-4.0',
);

// Geojson geometry 
$geometry=array();
if (isset($v['latitude']) && isset($v['longitude']) && $v['latitude']!="" && $v['longitude']!="") {

   $properties['distance']    = wgs84_distance($v['latitude'], $v['longitude'], $params['lat'], $params['lon']);
   $geometry=array(
      'type'=>"Point",
      'coordinates'=>array($v["longitude"], $v["latitude"])
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
$sortedfeatureslist = sortfeatures($featurelist, $params['orderby'], $params['orderdirection'], $params['limit']);


$outjson=array(array(
  "type"=> "FeatureCollection",
   "features"=> $sortedfeatureslist
));

print(json_encode($outjson,  JSON_PRETTY_PRINT));
die(1);

/*

Output: Basic geojson example
[
    {
        "type": "FeatureCollection",
        "features": [
            {
                "type": "Feature",
                "geometry": {
                    "type": "Point",
                    "coordinates": [
                        21.7692,
                        61.4859
                    ]
                },
                "properties": {
                    "name": "Aalto",
                    "popupContent": "<b>Aalto<\/b>\nKaupunginsairaalan piha; P\u00e4\u00e4rn\u00e4inen; 61.4859\u00b0N, 21.7692\u00b0E; Eero Hiironen; 1978; <a target='_blank' href='\/\/fi.m.wikipedia.org\/wiki\/file:Eero_Hiironen_Aalto_1980.jpg'>kuva<\/a><br><br>Reittihaku: <a target='_blank' href='https:\/\/www.google.com\/maps?saddr=My+Location&daddr=61.4859,21.7692'>google.com<\/a> ja <a target='_blank' href='https:\/\/opas.matka.fi\/POS\/Koordinaatit%2061.4859 N 21.7692 E ::61.4859,21.7692\/lahellasi'>matka.fi<\/a>",
                    "color": "green"
                }
            },

*/
?>
