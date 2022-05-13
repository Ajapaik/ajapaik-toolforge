<?php
require_once("common.php");
$start_ts=microtime();

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
header('Content-Type: text/html; charset=utf-8');

// Default GET parameters from Flutter APP
$params=array(
'lang'=>'et',
'qid'=>'Q3736402',
'latitude'=>59.436962,
'longitude'=>24.753574,
'orderby'=>'distance',
'orderdirection'=>'desc',
'search'=>'',
'limit'=>1000
);

$stringkeys=array('lang', 'qid', 'orderby', 'orderdirection', 'search');
$params=merge_get_parameters($params, $stringkeys);

function finnaUrlParameter($key, $value) {
	return rawurlencode($key) . "=" . rawurlencode($value);
}

// Read photos from Finna API
$url="https://api.finna.fi/v1/search";
$url.="?". finnaUrlParameter("filter[]", '~format:"0/Image/"');
$url.="&". finnaUrlParameter("filter[]", '~format:"0/Place/"');
$url.="&". finnaUrlParameter("filter[]", 'free_online_boolean:"1"');
$url.="&". finnaUrlParameter("filter[]", '~usage_rights_str_mv:"usage_B"');
$url.="&filter%5B%5D=%7B%21geofilt+sfield%3Dlocation_geo+pt%3D". $params["latitude"] ."%2C". $params["longitude"] . "+d%3D0.1%7D%3A%22%22";
$url.="&type=AllFields";
$url.="&lng=fi";
$url.="&prettyPrint=true";
$fields=array('id', 'title', 'images', 'imageRights', 'authors', 'source', 'geoLocations', 'recordPage','year', 'summary', 'rawData', 'places', 'events');

foreach ($fields as $field) {
	$url.="&" . finnaUrlParameter("field[]",$field);
}

die($url);
$file=file_get_contents($url);
$json=json_decode($file, true);

// Parse Finna request
foreach($json["records"] as $record)
{

	$author_str="";
	$delim="";
        foreach($json["records"]["authors"]["primary"] as $author)
	{
		foreach($author as $k=>$v) {
			$author_str.=$delim . $k;
			$delim=", ";
		}
	}
	if (isset($record["rawData"]) && isset($record["rawData"]["author_facet"])) {
		$author_str=implode(", ", $record["rawData"]["author_facet"]);
	}

	$source_labels=array();
	if (isset($record["rawData"]) && isset($record["rawData"]["building"])) {
		foreach($record["rawData"]["building"] as $building) {
			array_push($source_labels, $building["translated"]);
		}
	}
	if (isset($record["rawData"]) && isset($record["rawData"]["building"])) {
		array_push($source_labels, $record["id"]);
	} else
	{
		array_push($source_labels, $record["id"]);
	}

	$source_label=implode(", ", $source_labels);
	$date_str=$record["year"];
	if (isset($record["rawData"]) && isset($record["rawData"]["era_facet"])) {
		$date_str=$record["rawData"]["era_facet"][0];
	}

	$row=array();
	$row["id"]=$record["id"];
	$row["name"]=$record["title"];
	$row["description"]="";
	$row["date"]=$date_str;
	$row["author"]=$author_str;
	$row["source_label"]=$source_label;
	$row["source_url"]="https://finna.fi" . $record["recordPage"];;
	$row["thumbnail"]="https://finna.fi" . $record["images"][0];;
	$row["licence_url"]=$record["imageRights"]["copyright"];
	$row["licence_label"]=$record["imageRights"]["link"];

	if (isset($record["rawData"]) && isset($record["rawData"]["center_coords"])) {
		$coords=$record["rawData"]["center_coords"];
		die($coords);
	}
	print_r($row);
	die(1);
}



if ($params["qid"]!="") {
        $params=array_merge($params,  get_qid_information($params["qid"], $params["lang"]));
}




$sparqlurl="https://query.wikidata.org/sparql?format=json&query=%23Cats%0ASELECT%20DISTINCT%20%0A%20%3Fimage%20%3Fitem%20%0A%20%20%3FitemLabel%20%0A%20%20%3FitemDescription%20%0A%20%20%3Fcoordinates%20%0A%20%20%3Flocated_in_the_administrative_entity%20%0A%20%20%28YEAR%28%3Finception%29%20as%20%3Finception_year%29%20%20%0A%20%20%3Fenvironmental_register_code%20%0A%20%20%3Fenvironmental_register_code_formatter_url%0A%20%20%3Fworld_database_on_protected_areas_id%20%0A%20%20%3Fworld_database_on_protected_areas_formatter_url%0AWHERE%20%0A%7B%0A%20%20BIND%28wd%3A" . $params["qid"] ."%20as%20%3Fitem%29%20%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP625%20%3Fcoordinates%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP18%20%3Fimage%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP131%20%3Flocated_in_the_administrative_entity%20%7D%20%20%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP571%20%3Finception%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP373%20%3Fcommonscat%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP4689%20%3Fenvironmental_register_code%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP809%20%3Fworld_database_on_protected_areas_id%20%7D%0A%20%20OPTIONAL%20%7B%20wd%3AP4689%20wdt%3AP1630%20%3Fenvironmental_register_code_formatter_url%20%7D%0A%20%20OPTIONAL%20%7B%20wd%3AP809%20wdt%3AP1630%20%3Fworld_database_on_protected_areas_formatter_url%20%7D%0A%0A%0A%0A%20%20SERVICE%20wikibase%3Alabel%20%7B%20bd%3AserviceParam%20wikibase%3Alanguage%20%22%5BAUTO_LANGUAGE%5D%2Cen%22.%20%7D%0A%20%20%0A%7D";
//$sparqlurl=str_replace("__REPLACE__", rawurlencode($params["sparql"]), $sparqlurl);

$file=curl_get_contents($sparqlurl);
$json=json_decode($file, true);
$featurelist=array();


$thumbnailurl="";

foreach($json['results']['bindings'] as $k=>$v) {
   if (isset($v["coordinates"]))
   {
      if (preg_match("|Point\((\d+\.\d+) (\d+\.\d+)\)|ism", $v["coordinates"]["value"], $m))
      {
         $lon=$m[1];
         $lat=$m[2];

         $geometry=array(
            'type'=>"Point",
            'coordinates'=>array($lon, $lat)
         );
      }
   }
   else $geometry=Array();

   if (isset($v['image'])) {
      $filename=urldecode(str_replace("http://commons.wikimedia.org/wiki/Special:FilePath/", "", $v["image"]["value"]));
      $filename=str_replace(" ", "_", $filename);
      $thumbnailurl="https://upload.wikimedia.org/wikipedia/commons/thumb/" . substr(md5($filename),0,1) . "/".substr(md5($filename),0,2) ."/" . rawurlencode($filename).  "/640px-" . rawurlencode($filename);
   } else {
        continue;
   }

   $properties=array(
      'id'          =>$v['item']['value'],
      'name'        =>$v['itemLabel']['value'],
      'description' =>isset($v['itemDescription']) ? $v['itemDescription']['value'] : "",
      'date'        =>"",
      'author'      =>"",
      'source_url'  =>$v['item']['value'],
      'source_label'=>"Wikidata",
      'favorites'   =>"",
      'rephotos'    =>0,
      'thumbnail'   => isset($v['image']) ? $thumbnailurl : "",
      'distance'    => wgs84_distance($lat, $lon, $params['latitude'], $params['longitude']),
      'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
      'licence_label' => 'CC-BY-4.0',
   );
   $feature=array(
      'type'=>"Feature",
      'geometry'=>$geometry,
      'properties'=>$properties
   );
   if (!isset($featurelist[$properties["id"]])) $featurelist[$properties["id"]]=$feature;
   elseif (0) {
        print_r($featurelist[$properties["id"]]);
        print_r($feature);
        die("ERROR!!!");
   }
}

$sortedfeatureslist = sortfeatures(get_testfeatures(), $params['orderby'], $params['orderdirection'], $params['limit']);

$outjson=array(array(
  "type"=> "FeatureCollection",
  "@context"=> array(
        "https://geojson.org/geojson-ld/geojson-context.jsonld",
        "https://opengeospatial.github.io/ELFIE/contexts/elfie-2/elf-index.jsonld"
   ),
   "name" => $params["label"],
   "description" => (isset($params['extract']) && ($params['extract']!="")) ? $params["extract"] : $params["description"],
   "extract" => $params["extract"],
   "extract_url" => $params["sitelink_url"],
   "wikidataid" => $params["qid"],
   "features"=> $sortedfeatureslist,
   "image" => $thumbnailurl
));



print(json_encode($outjson,  JSON_PRETTY_PRINT));


?>
≈
