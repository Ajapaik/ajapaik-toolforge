<?php
require_once("common.php");
require_once("database.php");


$callback="";
if (isset($_GET['callback']) && trim($_GET['callback'])!="" && preg_match("|\A[a-zA-z0-9_]*\z|", $_GET['callback'])) {
   $callback=trim($_GET['callback']);
}


//https://ajapaik.ee/api/v1/album/nearest/?latitude=49.84189&longitude=24.0315&limit=100"

$params=array(
//'category'=>"Lasnamäe",
'category'=>"Kristiine",
'latitude'=>49.8418,
'longitude'=>24.0315,
'limit'=>100,
'search'=>'',
'orderby'=>'distance',
'orderdirection'=>'desc',
'mode'=>'file'
);

$stringkeys=array("search", "orderby", "orderdirection", "category", "mode");
$params=merge_get_parameters($params, $stringkeys);


print_to_error_log(print_r($_GET, true));

if ($callback!="") {
   header('Content-Type: application/javascript');
}
else
{
   header('Content-Type: application/json');
}
header("Access-Control-Allow-Origin: *");

if ($params["mode"]=='categories') {
   $fileicon="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a3/Font_Awesome_5_solid_images.svg/512px-Font_Awesome_5_solid_images.svg.png";
   $categoryicon="https://upload.wikimedia.org/wikipedia/commons/thumb/7/73/Font_Awesome_5_solid_folder-open_white.svg/512px-Font_Awesome_5_solid_folder-open_white.svg.png";
   $featurelist=get_categories($params);
}
else {
   $url="https://petscan.wmflabs.org/?psid=19026968&format=json&categories=" . urlencode($params['category']);
   if ($params["search"]!="") $url.="&regexp_filter=" .urlencode(".*?". $params["search"] . ".*?");
   $featurelist=get_petscan_featurelist($url, $params["mode"]);

   if (count($featurelist)>0) { 
      $categoryicon="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Font_Awesome_5_solid_folder.svg/512px-Font_Awesome_5_solid_folder.svg.png";
      $fileicon="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Font_Awesome_5_solid_images_white.svg/512px-Font_Awesome_5_solid_images_white.svg.png";
   }
   else
   {
      $fileicon="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a3/Font_Awesome_5_solid_images.svg/512px-Font_Awesome_5_solid_images.svg.png";
      $categoryicon="https://upload.wikimedia.org/wikipedia/commons/thumb/7/73/Font_Awesome_5_solid_folder-open_white.svg/512px-Font_Awesome_5_solid_folder-open_white.svg.png";
      $featurelist=get_categories($params);
   }
}

function get_categories($params) {
   $featurelist=array();
   $subicon="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d8/Font_Awesome_5_solid_folder-minus.svg/512px-Font_Awesome_5_solid_folder-minus.svg.png";
   $parenticon="https://upload.wikimedia.org/wikipedia/commons/thumb/1/18/Font_Awesome_5_solid_folder-plus.svg/512px-Font_Awesome_5_solid_folder-plus.svg.png";
   $cats=get_sub_cats($params["category"]);
   $featurelist=get_database_featurelist($featurelist, $cats, $params, $subicon);
   $cats=get_parent_cats($params["category"]);
   $featurelist=get_database_featurelist($featurelist, $cats, $params, $parenticon);
   return $featurelist;
}


function get_petscan_featurelist($url, $mode) {
   $file=file_get_contents($url);
   $json=json_decode($file, true);
   $featurelist=array();

   foreach($json['*'][0]['a']["*"] as $k=>$v) {

      if (isset($v["metadata"]) && isset($v["metadata"]["coordinates"]))
      {
         list($lat, $lon)=preg_split("|[/]|ism", $v["metadata"]["coordinates"]);
         $geometry=array(
            'type'=>"Point",
            'coordinates'=>array(floatval($lon), floatval($lat))
         );
      }
      else $geometry=Array();
      $thumbnailurl="https://upload.wikimedia.org/wikipedia/commons/thumb/" . substr(md5($v["title"]),0,1) . "/" .substr(md5($v["title"]),0,2) ."/" . urlencode($v["title"]).  "/640px-" . urlencode($v["title"]);


      $properties=array(
         'id'          =>$v['id'],
         'name'        =>str_replace("_", " ", preg_replace("/\.(JPG|JPEG|PNG|TIFF)/ism", "", $v['title'])) ,
         'description' =>isset($description) ? $description : "",
         'date'        =>"",
         'author'      =>"",
         'source_url'  =>"https://commons.wikimedia.org/wiki/" . $v["nstext"] . ":" . $v["title"],
         'source_label'=>"Wikimedia Commons",
         'favorites'   =>"",
         'rephotos'    =>0,
         'thumbnail'  =>$thumbnailurl,
         'distance'    => wgs84_distance($lat, $lon, $params['latitude'], $params['longitude']),
         'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
         'licence_label' => 'CC-BY-4.0',
      );

      $feature=array(
         'type'=>"Feature",
         'geometry'=>$geometry,
         'properties'=>$properties
      );
      $featurelist[$properties["id"]]= $feature;
   }
   return $featurelist;
}

function get_database_featurelist($featurelist,$cats, $params, $icon) {

/*
            [page_id] => 36929992
            [cl_to] => Turku
            [cat_id] => 282499270
            [cat_title] => Audio_files_of_Turku
            [cat_pages] => 2
            [cat_subcats] => 0
            [cat_files] => 2
            [page_image_free] => 
            [wikibase_item] => 
            [gt_lat] => 
            [gt_lon] => 
*/

   foreach($cats as $cat) {
      if ($cat["gt_lat"]!="" && $cat["gt_lon"]!="")
      {
         $geometry=array(
            'type'=>"Point",
            'coordinates'=>array(floatval($cat["gt_lon"]), floatval($cat["gt_lat"]))
         );
      }
      else $geometry=Array();


      if ($cat["page_image_free"]!="") {
         $thumbnailurl="https://upload.wikimedia.org/wikipedia/commons/thumb/" . substr(md5($cat["page_image_free"]),0,1) . "/" .substr(md5($cat["page_image_free"]),0,2) ."/" . urlencode($cat["page_image_free"]).  "/640px-" . urlencode($cat["page_image_free"]);
      }
      else
      {
         $thumbnailurl=$icon;
      }
      $id="https://commons.wikimedia.org/wiki/" . $cat["cat_title"];



   $geojsonurl="https://fiwiki-tools.toolforge.org/api/commonscat.php?category=" . urlencode($cat["cat_title"]) .""; 

      $properties=array(
         'id'          =>$id,
         'name'        =>str_replace("_", " ", $cat['cat_title']) ,
         'description' =>isset($description) ? $description : "",
         'date'        =>"",
         'author'      =>"",
         'source_url'  =>$id,
         'source_label'=>"Wikimedia Commons",
         'favorites'   =>"",
         'rephotos'    =>0,
         'thumbnail'  =>$thumbnailurl,
         'distance'    => wgs84_distance($cat["gt_lat"], $cat["gt_lon"], $params['latitude'], $params['longitude']),
         'geojson'    => $geojsonurl,
         'licence_url' => 'https://creativecommons.org/share-your-work/public-domain/cc0/',
         'licence_label' => 'CC0',

      );

      $feature=array(
         'type'=>"Feature",
         'geometry'=>$geometry,
         'properties'=>$properties
      );
      $featurelist[$properties["id"]]= $feature;
   }
   return $featurelist;
}



function button($url, $icon, $label) {
      $geometry=Array();

      $properties=array(
         'id'          =>$url,
         'name'        =>$label,
         'description' =>isset($description) ? $description : "",
         'date'        =>"",
         'author'      =>"",
         'source_url'  =>"https://commons.wikimedia.org/wiki/File:Font_Awesome_5_solid_arrow-circle-up.svg",
         'source_label'=>"Wikimedia Commons",
         'favorites'   =>"",
         'rephotos'    =>0,
         'thumbnail'  => $icon,
         'distance'    => 0,
         'geojson'    => $url,
         'licence_url' => 'https://creativecommons.org/share-your-work/public-domain/cc0/',
         'licence_label' => 'CC0',
      );

      $feature=array(
         'type'=>"Feature",
         'geometry'=>$geometry,
         'properties'=>$properties
      );
      return $feature;
}



//print_r($featurelist);
//die(1);

$sortedfeatureslist = sortfeatures($featurelist, $params['orderby'], $params['orderdirection'], $params['limit']);

if (preg_match("|[&]?mode=" . $params["mode"] ."|",  $_SERVER['REQUEST_URI'])) {
   $categoriesurl="https://fiwiki-tools.toolforge.org" . str_replace("mode=" . $params["mode"], "mode=categories", $_SERVER['REQUEST_URI']);
   $fileurl="https://fiwiki-tools.toolforge.org" . str_replace("mode=" . $params["mode"], "mode=file", $_SERVER['REQUEST_URI']);
}
else
{
   $categoriesurl="https://fiwiki-tools.toolforge.org" . $_SERVER['REQUEST_URI'] ."&mode=categories";
   $fileurl="https://fiwiki-tools.toolforge.org" . $_SERVER['REQUEST_URI'] ."&mode=file";
}
//die($parenturl);
$categorylabel=str_replace("_", " ", str_replace("Category:", "", $params["category"]));
/*
array_unshift($sortedfeatureslist, button(
   $fileurl,
   $fileicon,
   $categorylabel ));
*/

if ($params["mode"]=='file') {
array_unshift($sortedfeatureslist, button(
      $categoriesurl, 
      $categoryicon,
      "Related categories"));
}

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
