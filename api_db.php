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

// Get posible non-standard location of FTL's database
$FTLsettings = parse_ini_file("/etc/pihole/pihole-FTL.conf");
if(isset($FTLsettings["DBFILE"]))
{
	$DBFILE = $FTLsettings["DBFILE"];
}
else
{
	$DBFILE = "/etc/pihole/pihole-FTL.db";
}

// Needs package php5-sqlite, e.g.
//    sudo apt-get install php5-sqlite

function SQLite3_connect($trytoreconnect)
{
	global $DBFILE;
	try
	{
		// connect to database
		return new SQLite3($DBFILE, SQLITE3_OPEN_READONLY);
	}
	catch (Exception $exception)
	{
		// sqlite3 throws an exception when it is unable to connect, try to reconnect after 3 seconds
		if($trytoreconnect)
		{
			sleep(3);
			$db = SQLite3_connect(false);
		}
	}
}

if(strlen($DBFILE) > 0)
{
	$db = SQLite3_connect(true);
}
else
{
	die("No database available");
}
if(!$db)
{
	die("Error connecting to database");
}

if (isset($_GET['getAllQueries']) && $auth)
{
	$allQueries = array();
	if($_GET['getAllQueries'] !== "empty")
	{
		$from = intval($_GET["from"]);
		$until = intval($_GET["until"]);
		$results = $db->query('SELECT timestamp,type,domain,client,status FROM queries WHERE timestamp >= '.$from.' AND timestamp <= '.$until.' ORDER BY timestamp ASC');
		if(!is_bool($results))
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
	$results = $db->query('SELECT client,count(client) FROM queries '.$limit.' GROUP by client order by count(client) desc limit 20');

	$clients = array();
	$num = 0;

	if(!is_bool($results))
		while (($row = $results->fetchArray()) && $num < 10)
		{
			// Convert client to lower case
			$c = strtolower($row[0]);
			if(array_key_exists($c, $clients))
			{
				// Entry already exists, add to it (might appear multiple times due to mixed capitalization in the database)
				$clients[$c] += intval($row[1]);
			}
			else
			{
				// Entry does not yet exist
				$clients[$c] = intval($row[1]);
				// Increase number of clients
				$num++;
			}
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

	if(!is_bool($results))
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

	if(!is_bool($results))
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

	if(!is_bool($results))
		$result = array('mintimestamp' => $results->fetchArray()[0]);
	else
		$result = array();

	$data = array_merge($data, $result);
}

if (isset($_GET['getMaxTimestamp']) && $auth)
{
	$results = $db->query('SELECT MAX(timestamp) FROM queries');

	if(!is_bool($results))
		$result = array('maxtimestamp' => $results->fetchArray()[0]);
	else
		$result = array();

	$data = array_merge($data, $result);
}

if (isset($_GET['getQueriesCount']) && $auth)
{
	$results = $db->query('SELECT COUNT(timestamp) FROM queries');

	if(!is_bool($results))
		$result = array('count' => $results->fetchArray()[0]);
	else
		$result = array();

	$data = array_merge($data, $result);
}

if (isset($_GET['getDBfilesize']) && $auth)
{
	$filesize = filesize("/etc/pihole/pihole-FTL.db");
	$result = array('filesize' => $filesize);
	$data = array_merge($data, $result);
}

if (isset($_GET['getGraphData']) && $auth)
{
	$limit = "";

	if(isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".intval($_GET["from"])." AND timestamp <= ".intval($_GET["until"]);
	}
	elseif(isset($_GET["from"]) && !isset($_GET["until"]))
	{
		$limit = " AND timestamp >= ".intval($_GET["from"]);
	}
	elseif(!isset($_GET["from"]) && isset($_GET["until"]))
	{
		$limit = " AND timestamp <= ".intval($_GET["until"]);
	}

	$interval = 600;

	if(isset($_GET["interval"]))
	{
		$q = intval($_GET["interval"]);
		if($q > 10)
			$interval = $q;
	}

	// Count permitted queries in intervals
	$results = $db->query('SELECT (timestamp/'.$interval.')*'.$interval.' interval, COUNT(*) FROM queries WHERE (status == 2 OR status == 3)'.$limit.' GROUP by interval ORDER by interval');

	$domains = array();

	if(!is_bool($results))
		while ($row = $results->fetchArray())
		{
			$domains[$row[0]] = intval($row[1]);
		}
	$result = array('domains_over_time' => $domains);
	$data = array_merge($data, $result);

	// Count blocked queries in intervals
	$results = $db->query('SELECT (timestamp/'.$interval.')*'.$interval.' interval, COUNT(*) FROM queries WHERE (status == 1 OR status == 4 OR status == 5)'.$limit.' GROUP by interval ORDER by interval');

	$addomains = array();

	if(!is_bool($results))
		while ($row = $results->fetchArray())
		{
			$addomains[$row[0]] = intval($row[1]);
		}
	$result = array('ads_over_time' => $addomains);
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
