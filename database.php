<?php
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
$db_commonswiki = mysqli_connect('commonswiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password']);

unset($ts_mycnf, $ts_pw);
mysqli_select_db($db_commonswiki, 'commonswiki_p') or  die('Mysql select db failed: ' . mysqli_error($db_commonswiki));

function get_parent_cats($categorytitle) {
        global $db_commonswiki;
        $ret=array();

        $query="SELECT p.page_id, cl_to, c1.*, pp2.pp_value as page_image_free, pp3.pp_value as wikibase_item, gt_lat, gt_lon  FROM page as p, categorylinks as cl1, category as c1, page as p2 ";
        $query.=" LEFT JOIN page_props AS pp1 ON p2.page_id=pp1.pp_page AND pp1.pp_propname='hiddencat'";
        $query.=" LEFT JOIN page_props AS pp2 ON p2.page_id=pp2.pp_page AND pp2.pp_propname='page_image_free'";
        $query.=" LEFT JOIN page_props AS pp3 ON p2.page_id=pp3.pp_page AND pp3.pp_propname='wikibase_item'";
        $query.=" LEFT JOIN geo_tags AS gt ON gt.gt_page_id=p2.page_id ";
//        $query.=" LEFT JOIN page_props AS pp1 ON p2.page_id=pp1.pp_page ";
//        $query.=" LEFT JOIN page_props AS pp2 ON p2.page_id=pp1.pp_page ";
        $query.=" WHERE p.page_namespace=14";
        $query.=" AND p.page_title LIKE '%s'";
        $query.=" AND cl1.cl_from=p.page_id";
        $query.=" AND cl1.cl_to=p2.page_title";;
        $query.=" AND p2.page_namespace=14";
        $query.=" AND pp1.pp_propname IS NULL";
        $query.=" AND c1.cat_title=p2.page_title";
//        $query.=" AND cat";
        $query.=" GROUP BY p2.page_id ORDER BY cat_pages DESC LIMIT 200";
        $query=sprintf($query, mysqli_real_escape_string($db_commonswiki, $categorytitle));
//        $query.=" actor_name LIKE '" . mysqli_real_escape_string($db_fiwiki, $username) ."'";
//        $query.=" OR actor_name LIKE '" . mysqli_real_escape_string($db_fiwiki, ucfirst($username)) ."'"; 

         $result = mysqli_query($db_commonswiki, $query);
        if (!$result) {
                $message  = 'Invalid query: ' . mysqli_error($db_commonswiki) . "\n";
                $message .= 'Whole query: ' . $query;
                die($message);
        }
        while ($row = mysqli_fetch_assoc($result)) {
                array_push($ret, $row);
        }
        return $ret;
}
function get_sub_cats($categorytitle) {
        global $db_commonswiki;
        $ret=array();

        $query="SELECT p.page_id, cl_to, c1.*, pp2.pp_value as page_image_free, pp3.pp_value as wikibase_item, gt_lat, gt_lon  FROM categorylinks as cl1, category as c1, page as p ";
        $query.=" LEFT JOIN page_props AS pp1 ON p.page_id=pp1.pp_page AND pp1.pp_propname='hiddencat'";
        $query.=" LEFT JOIN page_props AS pp2 ON p.page_id=pp2.pp_page AND pp2.pp_propname='page_image_free'";
        $query.=" LEFT JOIN page_props AS pp3 ON p.page_id=pp3.pp_page AND pp3.pp_propname='wikibase_item'";
        $query.=" LEFT JOIN geo_tags AS gt ON gt.gt_page_id=p.page_id ";
//        $query.=" LEFT JOIN page_props AS pp1 ON p2.page_id=pp1.pp_page ";
//        $query.=" LEFT JOIN page_props AS pp2 ON p2.page_id=pp1.pp_page ";
        $query.=" WHERE cl1.cl_to LIKE '%s'";
        $query.=" AND p.page_id=cl1.cl_from";
        $query.=" AND p.page_namespace=14";
        $query.=" AND pp1.pp_propname IS NULL";
        $query.=" AND c1.cat_title=p.page_title";
//        $query.=" AND cat";
        $query.=" GROUP BY p.page_id ORDER BY cat_pages DESC LIMIT 200";
        $query=sprintf($query, mysqli_real_escape_string($db_commonswiki, $categorytitle));
//        $query.=" actor_name LIKE '" . mysqli_real_escape_string($db_fiwiki, $username) ."'";
//        $query.=" OR actor_name LIKE '" . mysqli_real_escape_string($db_fiwiki, ucfirst($username)) ."'"; 

         $result = mysqli_query($db_commonswiki, $query);
        if (!$result) {
                $message  = 'Invalid query: ' . mysqli_error($db_commonswiki) . "\n";
                $message .= 'Whole query: ' . $query;
                die($message);
        }
        while ($row = mysqli_fetch_assoc($result)) {
                array_push($ret, $row);
        }
        return $ret;
}

//print_r(get_sub_cats("Turku"));

?>
