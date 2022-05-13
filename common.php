<?php

function print_to_error_log($str) {
	$select = urldecode($str);
        $file2 = fopen("../../logs/error.txt", "a");
        fwrite($file2 , $select); 
        fclose($file2 );
}

function curl_get_contents ($url) {
  $curl = curl_init();
  $useragent="Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1); ZacheBot";

  curl_setopt($curl, CURLOPT_USERAGENT, $useragent);

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_URL, $url);
  $html = curl_exec($curl);
  curl_close($curl);
  return $html;
}

// http://www.codecodex.com/wiki/Calculate_distance_between_two_points_on_a_globe#PHP
function  wgs84_distance($latitude1, $longitude1, $latitude2, $longitude2) {
	if ( 
		(isset($latitude1)) && 
		(isset($latitude2)) && 
		(isset($longitude1)) && 
		(isset($longitude2)) && 
		($latitude1!="" && 
		$latitude2!="" && 
		$longitude1!="" && 
		$longitude2!="")) {


        $earth_radius = 6371;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
        return $d;

     }
     return ""; 
}

function get_qid_information($qid="", $lang="en") {
	$params=array();
	$params["qid"]=$qid;
	$params["language"]=$lang;
	

	if ($qid!="") {
        	$url="https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&props=info%7Csitelinks%7Clabels%7Cdescriptions&languages=" .$lang ."%7Cen%7Cet%7Cfi%7Csv%7Cno%7Cnb%7Csms%7Cse%7Csmn&languagefallback=1&ids=" . $qid;
        	$file=file_get_contents($url);
	        $json=json_decode($file, true);

        	if (isset($json["entities"]) && isset($json["entities"][$qid]))
	        {
        	        $wd=$json["entities"][$qid];
                	$params["label"]=$wd["labels"][$lang]["value"];;
	                $params["description"]=$wd["descriptions"][$lang]["value"];
        	        if (isset($wd["sitelinks"][$lang ."wiki"])) {
                	        $params["sitelink"]=$wd["sitelinks"][$lang ."wiki"]["title"];
	                        $params["sitelink_url"]="https:\/\/" . $lang .".wikipedia.org/wiki/" . str_replace(" ", "_", $params["sitelink"]);
        	                $url="https://". $lang . ".wikipedia.org/w/api.php?action=query&format=json&prop=description%7Cextracts&list=&exsentences=3&exintro=1&explaintext=1&exsectionformat=plain&titles=" . urlencode($params["sitelink"]);
                	        $file=file_get_contents($url);
	                        $json=json_decode($file, true);
        	                if (isset($json["query"]) && isset($json["query"]["pages"]))
                	        {
                        	        $page=$json["query"]["pages"];
                                	foreach($page as $k=>$v)
	                                {
        	                                if (isset($v["extract"])) {
                	                                $params["extract"]=$v["extract"];
                        	                        $params["extract"]=preg_replace("|([^\s])\(|ism", "$1 (", $params["extract"]);
                                	                $params["extract"]=preg_replace("/\r|\n/ism", " ", $params["extract"]);
                                        	}
	                                        break;
        	                        }

                	        }

	                }
        	}
		return $params;
	}
}


function merge_get_parameters($params, $stringkeys) {
//	$stringkeys=array('qid', 'sparql', 'orderby', 'orderdirection');
	foreach ($params as $k=>$v) {
        	if (isset($_GET[$k]) && trim($_GET[$k])!="") {
                	if (in_array($k, $stringkeys))
	                {
        	                $params[$k]=urldecode($_GET[$k]);
	                }
        	        else
                	{
                        	$params[$k]=trim($_GET[$k])*1;
	                }
        	}
	}
	return $params;
}


function sortfeatures($featurelist, $orderby, $orderdirection, $limit, $search="")
{
   $sortarray=array();
   if (1 || $orderby=='distance')
//   if ($orderby=='distance')
   {
        foreach($featurelist as $k=>$f) {

		if ( (!isset($f["properties"]["distance"])) ||
		    ($f["properties"]["distance"]=="")) {
			$sortarray["" . $k]=99999;
		}
		else
		{
			$sortarray["" . $k]=$f["properties"]["distance"];
		}
	}
   }
   else
   {
        foreach($featurelist as $k=>$f) $sortarray["".$k]=$f["properties"]["name"];
   }
   if ($orderdirection=='desc') asort($sortarray);
   else arsort($sortarray);

   $out=array();
   $n=0;


   foreach ($sortarray as $k=>$v)
   {
	if ($search!="") {
		$tmp=print_r( $featurelist[intval($k)], true);
		if (!preg_match("|" .$search ."|ism", $tmp)) continue;
	}
        $n++;
        if ($n>$limit) break;
        array_push($out, $featurelist[intval($k)]);
   }
   return $out;
}


function get_testfeatures() {
   $url="https://commons.wikimedia.org/w/index.php?title=User:Zache/test.json&action=raw";
   $file=file_get_contents($url);
   $json=json_decode($file, true);
   $featurelist=array();
   foreach($json as $k=>$v) {

      if (isset($v["latitude"]) && isset($v["longitude"]))
      {
         $geometry=array(
            'type'=>"Point",
            'coordinates'=>array($v['latitude'], $v['longitude'])
         );
      }
      else $geometry=Array();

      $filename=$v["title"];

      $thumbnailurl="https://upload.wikimedia.org/wikipedia/commons/thumb/" . substr(md5($filename),0,1) . "/" .substr(md5($filename),0,2) ."/" . rawurlencode($filename).  "/640px-" . rawurlencode($filename);


      $properties=array(
         'id'          =>$v['thumbnailUrl'],
         'name'        =>$v['title'],
         'description' =>isset($v['description']) ? $v['description'] : "",
         'date'        =>"",
         'author'      =>"",
         'source_url'  =>$v['url'],
         'source_label'=>"Wikimedia Commons",
         'favorites'   =>"",
         'rephotos'    =>0,
         'thumbnail'  =>$v['thumbnailUrl'],
      'distance'    => wgs84_distance($lat, $lon, $params['latitude'], $params['longitude']),
      'licence_url' => 'https://creativecommons.org/licenses/by/4.0/deed.fi',
      'licence_label' => 'CC-BY-4.0',

      );

      $feature=array(
         'type'=>"Feature",
         'geometry'=>$geometry,
         'properties'=>$properties
      );
      $featurelist[$properties['id']]= $feature;
   }

   return $featurelist;
}


?>
