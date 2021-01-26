<h3><a href="?upload">Upload&test your own replay here</a></h3>

<?php
$folder = "testreplays".DIRECTORY_SEPARATOR;
$filename=$folder."PvP Yshia Pydracor(FS), Yasime(NO) vs dreaddy(FN), Xerador(SOF) 2009-08-05 01-01-10.pmv";
$filename=$folder."PvP Fyre dreaddy(FNN), Xerador(SOF) vs Shinlol(FOF), Linne(FNN) 2009-08-06 16-53-39.pmv";


//$filename="6062_PvP_1on1_Peyon_vs_Ayrez_on_Random.pmv";
//$filename="only_ww_in_deck.pmv";
//$filename = "19.01.2021, 02_15_59 randommap_GeneratedMap mit dreaddy (ONNX) vs pl_Player5 () in 14_19_3.pmv";
$filename = $folder."19.01.2021, 02_15_59 randommap_GeneratedMap mit dreaddy (ONNX) vs pl_Player5 () in 14_19_3.pmv";
$filename = $folder."twilight_expert_autosave.pmv";
$filename = $folder."PvP Fyre dreaddy(FNN), Xerador(SOF) vs Shinlol(FOF), Linne(FNN) 2009-08-06 16-53-39.pmv";

$filename = $folder."19.01.2021, 02_15_59 randommap_GeneratedMap mit dreaddy (ONNX) vs pl_Player5 () in 14_19_3.pmv";
$filename = $folder."rpve_22012021_fire.pmv";


if(isset($_GET["upload"])){


    if(!empty($_FILES["replayupload"])){
        $filename = $_FILES["replayupload"]["tmp_name"];
    }else{

?>

<form method="post" enctype="multipart/form-data">
    <div>
        <p>
            <label for="image_uploads">Choose your replayfile (.pmv). Usually stored in (userdata)\Documents\BattleForge\replays\ </label>
        </p>
        <input type="file" id="replayupload" name="replayupload" accept=".pmv" multiple />
    </div>
    <div>
        <p>
            <button>Submit</button>
        </p>
    </div>
</form>

    <?php
        die();
    }
}






define('SL_DEBUG', false);

require_once("slreplayparser/replayparser.inc.php");


$cardbase = SkylordsCardbase::getInstance();
$cardbase->loadCards();



// $filename="only_ww_in_deck.pmv";


echo "loading: ".$filename;echo "<br>";

ob_start();
$parser = new SkylordsReplayParser($filename);
$replaydata = $parser->loadData();
$warnings=ob_get_clean();
// echo $warnings;
echo "<p>Mapname: ".$replaydata->mapname."</p>";
echo "<p>Mapfilename: ".$replaydata->mapfilename."</p>";
echo "<p>Maptype: ".$replaydata->maptype."</p>";

if($replaydata->maptype != "PVP")
echo "<p>Difficulty: ".$replaydata->difficulty."</p>";

echo "<p>Length of Replay: ".$replaydata->getReplayTimeString()."</p>";
echo "<p>Winner: ".$replaydata->getWinnerteamString()."</p>";


foreach($replaydata->teams as $team){



    echo "<hr>";
    echo "<h3>Group:".$team->id."(".$team->name.")"."</h5>";


    foreach($replaydata->getPlayerByGroup($team->id) as $player){

        echo "<div style='padding-top:5px'><h4>".$player->name."</h4>";

        echo "<h5>APM:".$player->apm."</h5>";
        echo "<h5>Monuments: ".$player->getOrbsHtml()."</h5>";
        // echo "<p>Deck:".$player->getDeckStringFromPlayedCards()."</p>";

        echo "<h5>Deck:<div style='border:1px solid black;padding:5px;'>".$player->getDeckHtmlFromPlayedCards()."</div></h5>";
        echo "</div>";

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

