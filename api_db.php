<?php
/*   Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license */

$api = true;
header('Content-type: application/json');
require("scripts/pi-hole/php/password.php");
require("scripts/pi-hole/php/auth.php");
check_cors();

$data = array();

// Needs package php5-sqlite, e.g.
//    sudo apt-get install php5-sqlite

$db = new SQLite3('/etc/pihole/pihole-FTL.db');
if(!$db)
	die("Cannot access database");

// Long-Term API functions
// if (isset($_GET['test']))
// {
// 	// $results = $db->query('SELECT * FROM QUERIES order by TIMESTAMP ASC LIMIT 2');
// 	$results = $db->query('SELECT TIMESTAMP,TYPE,DOMAIN,CLIENT,STATUS FROM QUERIES order by TIMESTAMP ASC');
// 	echo "N=".$result->numColumns;
// 	while ($row = $results->fetchArray())
// 		var_dump($row);
// }

if (isset($_GET['getAllQueries']) && $auth)
{
	if($_GET['getAllQueries'] === "empty")
	{
		$allQueries = array();
	}
	else
	{
		$from = intval($_GET["from"]);
		$until = intval($_GET["until"]);
		$results = $db->query('SELECT timestamp,type,domain,client,status FROM queries WHERE timestamp >= '.$from.' AND timestamp <= '.$until.' ORDER BY timestamp ASC');
		$allQueries = array();
		while ($row = $results->fetchArray())
		{
			$allQueries[] = [$row[0],$row[1] == 1 ? "IPv4" : "IPv6",$row[2],$row[3],$row[4]];
		}
	}
	$result = array('data' => $allQueries);
	$data = array_merge($data, $result);
}

if (isset($_GET['topClients']) && $auth)
{
	// $from = intval($_GET["from"]);
	$limit = "";
	if(isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = "WHERE timestamp >= ".$_GET["from"]." AND timestamp <= ".$_GET["until"];
	}
	elseif(isset($_GET["from"]) && !isset($_GET["until"]))
	{
		$limit = "WHERE timestamp >= ".$_GET["from"];
	}
	elseif(!isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = "WHERE timestamp <= ".$_GET["until"];
	}
	$results = $db->query('SELECT client,count(client) FROM queries '.$limit.' GROUP by client order by count(client) desc limit 10');
	$clients = array();
	while ($row = $results->fetchArray())
	{
		$clients[$row[0]] = intval($row[1]);
		// var_dump($row);
	}
	$result = array('top_sources' => $clients);
	$data = array_merge($data, $result);
}

if (isset($_GET['topDomains']) && $auth)
{
	$limit = "";

	if(isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".$_GET["from"]." AND timestamp <= ".$_GET["until"];
	}
	elseif(isset($_GET["from"]) && !isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".$_GET["from"];
	}
	elseif(!isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp <= ".$_GET["until"];
	}
	$results = $db->query('SELECT domain,count(domain) FROM queries WHERE (STATUS == 2 OR STATUS == 3)'.$limit.' GROUP by domain order by count(domain) desc limit 10');
	$domains = array();
	while ($row = $results->fetchArray())
	{
		$domains[$row[0]] = intval($row[1]);
	}
	$result = array('top_domains' => $domains);
	$data = array_merge($data, $result);
}

if (isset($_GET['topAds']) && $auth)
{
	$limit = "";

	if(isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".$_GET["from"]." AND timestamp <= ".$_GET["until"];
	}
	elseif(isset($_GET["from"]) && !isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".$_GET["from"];
	}
	elseif(!isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp <= ".$_GET["until"];
	}
	$results = $db->query('SELECT domain,count(domain) FROM queries WHERE (STATUS == 1 OR STATUS == 4)'.$limit.' GROUP by domain order by count(domain) desc limit 10');
	$addomains = array();
	while ($row = $results->fetchArray())
	{
		$addomains[$row[0]] = intval($row[1]);
	}
	$result = array('top_ads' => $addomains);
	$data = array_merge($data, $result);
}

if (isset($_GET['getMinTimestamp']) && $auth)
{
	$results = $db->query('SELECT MIN(timestamp) FROM queries');
	$result = array('mintimestamp' => $results->fetchArray()[0]);
	$data = array_merge($data, $result);
}

if (isset($_GET['getMaxTimestamp']) && $auth)
{
	$results = $db->query('SELECT MAX(timestamp) FROM queries');
	$result = array('maxtimestamp' => $results->fetchArray()[0]);
	$data = array_merge($data, $result);
}

if (isset($_GET['getQueriesCount']) && $auth)
{
	$results = $db->query('SELECT COUNT(timestamp) FROM queries');
	$result = array('count' => $results->fetchArray()[0]);
	$data = array_merge($data, $result);
}

if (isset($_GET['getDBfilesize']) && $auth)
{
	$filesize = filesize("/etc/pihole/pihole-FTL.db");
	$result = array('filesize' => $filesize);
	$data = array_merge($data, $result);
}

if(isset($_GET["jsonForceObject"]))
{
	echo json_encode($data, JSON_FORCE_OBJECT);
}
else
{
	echo json_encode($data);
}
