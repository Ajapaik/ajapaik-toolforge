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


$params=array(
'lang'=>'',
);
$stringkeys=array('lang');
$params=merge_get_parameters($params, $stringkeys);

$url="https://commons.wikimedia.org/w/index.php?title=User:Zache/projects.json&action=raw";
$file=file_get_contents($url);
$json=json_decode($file, true);

$outjson=array();
foreach($json as $j)
{
	if (isset($j["projectWikidataId"]) && preg_match("|\AQ\d+\z|ism", $j["projectWikidataId"]))
	{
		$lang="";
		if (isset($params["lang"]) && $params["lang"]!="") $lang=$params["lang"];
		elseif (isset($j["defaultlanguage"])) $lang=$j["defaultlanguage"];

		if ($lang!="") {
			$params=array_merge($params,  get_qid_information($j["projectWikidataId"], $lang));
			$j["name"]=$params["label"];
		}
	}
	array_push($outjson, $j);

}

print(json_encode($outjson,  JSON_PRETTY_PRINT));


?>
