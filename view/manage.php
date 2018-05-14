<?php

if (!User::isLoggedIn()) {
    return $app->redirect("/login/");
}
$charID = User::getUserID();

global $chars;
$c = [];
foreach ($chars as $ch) {
        $c[] = $ch['characterID'];
}

if (sizeof($c) == 0) {
	$app->redirect('/logout/');
}

if ($_POST) {
	$removeID = (int) @$_POST['remove'];
	if ($removeID > 0) {
		if (in_array($removeID, $c)) {
			Db::execute("delete from skq_character_info where characterID = :charID", [':charID' => $removeID]);
			Db::execute("delete from skq_scopes where characterID = :charID", [':charID' => $removeID]);
			Db::execute("delete from skq_character_associations where char1 = :charID or char2 = :charID", [':charID' => $removeID]);;
		}
		return;
	}
	$orderBy = (string) @$_POST['orderBy'];
	if (in_array($orderBy, ['characterName', 'balance desc', 'skillPoints desc', 'queueFinishes', 'customOrder'])) {
		UserConfig::set('orderBy', $orderBy);
		foreach ($c as $charID) {
			$custom = (int) $_POST["custom-$charID"];
			Db::execute("update skq_character_info set customOrder = :custom where characterID = :charID", [':charID' => $charID, 'custom' => $custom]);
		}
	}
	$app->redirect('/manage/');
}

$orderBy = UserConfig::get("orderBy", "skillPoints desc");
$scopes = Db::query("select * from skq_character_info where characterID in (" . implode(',', $c) . ") order by $orderBy");
Info::addInfo($scopes);
return $app->render("manage.html", ["scopes" => $scopes, 'orderBy' => $orderBy]);
