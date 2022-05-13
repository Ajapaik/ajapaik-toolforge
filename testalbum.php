<?php
require_once("common.php");


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
header("Access-Control-Allow-Origin: *");

//https://ajapaik.ee/api/v1/album/nearest/?latitude=49.84189&longitude=24.0315&limit=100"
$params=array(
'latitude'=>49.8418,
'longitude'=>24.0315,
'limit'=>100,
'search'=>'',
'orderby'=>'distance',
'orderdirection'=>'desc',
'page' => ''
);

$stringkeys=array("search", "orderby", "orderdirection", "page");
$params=merge_get_parameters($params, $stringkeys);


$url="https://commons.wikimedia.org/w/index.php?title=User:Zache/test.json&action=raw";
$file=file_get_contents($url);


$json=json_decode($file, true);
$featurelist=array();
foreach($json as $k=>$v) {

if (isset($v["longitude"]) && isset($v["latitude"]))
{
   $geometry=array(
      'type'=>"Point",
      'coordinates'=>array($v['longitude'], $v['latitude'])
   );
}
else $geometry=Array();

$thumbnailurl="https://upload.wikimedia.org/wikipedia/commons/thumb/" . substr(md5($v["title"]),0,1) . "/" .substr(md5($v["title"]),0,2) ."/" . urlencode($v["title"]).  "/1280px-" . urlencode($v["title"]);

$properties=array(
   'id'          =>$v['id'],
   'name'        =>$v['title'],
   'description' =>isset($v['description']) ? $v['description'] : "",
   'date'        =>$v['date'],
   'author'      =>$v['author'],
   'source_url'  =>$v['url'],
   'source_label'=>"Wikimedia Commons",
   'favorites'   =>"",
   'rephotos'    =>0,
   'thumbnail'  =>$v['thumbnailUrl'],
   'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
   'licence_label' => 'CC-BY-4.0'
);
   


$feature=array(
   'type'=>"Feature",
   'geometry'=>$geometry,
   'properties'=>$properties

);
array_push($featurelist, $feature);
}
$sortedfeatureslist = sortfeatures($featurelist, $params['orderby'], $params['orderdirection'], $params['limit'], $params["search"]);

$outjson=array(array(
  "type"=> "FeatureCollection",
   "features"=> $sortedfeatureslist
));

print(trim(json_encode($outjson,  JSON_PRETTY_PRINT)));
die(1);

/*
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
