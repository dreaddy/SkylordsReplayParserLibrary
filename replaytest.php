<?php

define('SL_DEBUG', false);

require_once("slreplayparser/replayparser.inc.php");


$cardbase = SkylordsCardbase::getInstance();
$cardbase->loadCards();



// $filename="only_ww_in_deck.pmv";


$filename="PvP Yshia Pydracor(FS), Yasime(NO) vs dreaddy(FN), Xerador(SOF) 2009-08-05 01-01-10.pmv";
$filename="PvP Fyre dreaddy(FNN), Xerador(SOF) vs Shinlol(FOF), Linne(FNN) 2009-08-06 16-53-39.pmv";
$filename="19.01.2021, 02_15_59 randommap_GeneratedMap mit dreaddy (ONNX) vs pl_Player5 () in 14_19_3.pmv";
$filename="PvP Fyre dreaddy(FNN), Xerador(SOF) vs Shinlol(FOF), Linne(FNN) 2009-08-06 16-53-39.pmv";
//$filename="6062_PvP_1on1_Peyon_vs_Ayrez_on_Random.pmv";
//$filename="only_ww_in_deck.pmv";

echo "loading: ".$filename;echo "<br>";

ob_start();
$parser = new SkylordsReplayParser("testreplays/".$filename, true);
$replaydata = $parser->loadData();
$warnings=ob_get_clean();

echo "<p>Mapname: ".$replaydata->mapname."</p>";

echo "<p>Duration of Replay: ".$replaydata->getReplayTimeString()."</p>";
echo "<p>Winner: ".$replaydata->getWinnerteamString()."</p>";


foreach($replaydata->teams as $team){

    // exclude npc teams
    if($team->isNpc){
        continue;
    }

    echo "<hr>";
    echo "<h5>Group:".$team->id."(".$team->name.")"."</h5>";


    foreach($replaydata->getPlayerByGroup($team->id) as $player){

        echo "<h5>Player:".$player->name."(".$player->id_in_group.")"."</h5>";

        echo "<p>APM:".$player->apm."</p>";
        echo "<p>Monuments:".$player->getOrbsString()."</p>";
        echo "<p>Deck:".$player->getDeckString()."</p>";



    }

}

echo "<pre>";
echo "<b>Rawdata:</b><br><br>";
echo json_encode($replaydata, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<pre>";
echo "<b>Errors&Warnings:</b><br><br>";
echo $warnings;
echo "</pre>";

