<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;

global $clientID, $secretKey, $callbackURL, $scopes;
$sso = new CrestSSO($clientID, $secretKey, $callbackURL, $scopes);

Db::execute("delete from skq_corporations where corporationID = 0");
Db::execute("delete from skq_alliances where allianceID = 0");

$count = 0;
$minute = date("Hi");
while ($minute == date("Hi") && $redis->get("skq:tqStatus") == "ONLINE") {
	$corps = Db::query("select * from skq_corporations where lastUpdate < date_sub(now(), interval 24 hour) order by lastUpdate limit 10");
	foreach ($corps as $corp) {
        if ($minute !== date('Hi')) break;
		$corpID = $corp['corporationID'];
		$url = "https://esi.evetech.net/v4/corporations/$corpID/";
		$return = $sso->doCall($url, [], null);
		$json = json_decode($return, true);
		if (isset($json['name'])) {
			$name = $json['name'];
			Db::execute("update skq_corporations set corporationName = :name, lastUpdate = now() where corporationID = :id", [':id' => $corpID, ':name' => $name]);
			$count++;
            sleep(1);
		}
	}
	$allis = Db::query("select * from skq_alliances where lastUpdate < date_sub(now(), interval 24 hour) order by lastUpdate limit 10");
	foreach ($allis as $alli) {
        if ($minute !== date('Hi')) break;
		$alliID = $alli['allianceID'];
		$url = "https://esi.evetech.net/v3/alliances/$alliID/";
		$return = $sso->doCall($url, [], null);
		$json = json_decode($return, true);
		if (isset($json['name'])) {
			$name = $json['name'];
			Db::execute("update skq_alliances set allianceName = :name, lastUpdate = now() where allianceID = :id", [':id' => $alliID, ':name' => $name]);
			$count++;
            sleep(1);
		}
	}
	sleep(1);
}
if ($count > 0) Util::out("Entities Processed $count => " . number_format($count / 60, 1) . "rps");

