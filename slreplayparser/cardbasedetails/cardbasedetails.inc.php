<?php

/** Skylords Reborn PHP Replay Parser
 *
 *  Copyright © 2021:  Torsten Lüders Website:http://torsten-lueders.de Mail:info@torsten-lueders.de SkylordsReborn: dreaddy
 *  Thanks to Dennis Gravel for his help back in 2009 figuring out the Fileformat / providing me the Source of his Java parser and to the Skylords Reborn Team for putting years of free work in reviving the game
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."../utils/slutils.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."carddetail.inc.php");

/**
 * cardloader for data from carddata.json or https://cardbase.skylords.eu/Cards/GetCards
 * Used to assign the correct Carddata for the playerdecks and actions
 */
class SkylordsCardbaseDetails{

    function __construct(){
        $this->loadCards();
    }

    private static $instance = null;

    /**
     * Summary of getInstance
     * @return SkylordsCardbaseDetails
     */
    static function getInstance(){

        if(empty(SkylordsCardbaseDetails::$instance)){
            SkylordsCardbaseDetails::$instance = new SkylordsCardbaseDetails();
        }

        return SkylordsCardbaseDetails::$instance;

    }

    /**
     * official apilink for the carddata
     * @var string
     */
    public $remoteUrlCardbase = "https://cardbase.skylords.eu/Cards/GetCards";

    /**
     * local json carddata
     * @var string
     */
    public $localPathCardbase = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."GetCardsCardbase.min.json";

    /**
     * local json carddata
     * @var string
     */
    public $localPath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."carddetailswithid.min.json";

    /**
     * local json carddata
     * @var string
     */
    public $localPathPretty = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."carddetailswithid.json";


    /**
     * Summary of $cards
     * @var SkylordsCardDetail[]
     */
    public $cards = array();



    /**
     * Create json with ids from official cardbase json
     * @param mixed $remote
     */
    private function loadCardsFromCardbase($remote = false){


        $content_json = null;

        if($remote){
            $content_json = file_get_contents($this->remoteUrlCardbase);
        }else{
            $content_json = file_get_contents($this->localPathCardbase);
        }

        $content = json_decode($content_json,false, 512, JSON_THROW_ON_ERROR);

        $this->cards=array();


        $cardidsUsed = array(); // card may exist multiple times. we want both ids then not only one. Hopefully the order is the same as in die marketplace api
        foreach($content->Result as $data){

            $cardnameCleaned = trim(str_replace(" (promo)", "", $data->Name)); // name in cardapi is xy (promo) for promocards. Not in marketplace api . . . 

            $card = SkylordsCardbase::getInstance()->getCardByName($cardnameCleaned, $cardidsUsed);
            $data->cardId = $card->cardId;
           // echo "id is ".$card->cardId;
            $this->cards[$data->cardId]=new SkylordsCardDetail($data);
            $cardidsUsed[$data->cardId]=$data->cardId;

        }


    }

    function loadCards(){

        if(!file_exists($this->localPath)){
            $this->recreateFromCardbase();
        }

        $content_json = null;

        $content_json = file_get_contents($this->localPath);

        $content = json_decode($content_json,false, 512, JSON_THROW_ON_ERROR);

        $this->cards=array();

        foreach($content as $data){
            $this->cards[$data->cardId]=new SkylordsCardDetail($data);
        }


    }

    /**
     * retrieve Carddata of specific card. To get the id used here use sl_extractCardkey
     * @param int $cardid
     * @return SkylordsCardDetail
     */
    function getCardById($cardid){


        if(isset($this->cards[$cardid])){
            return $this->cards[$cardid];
        }

        if(SL_DEBUG){
            echo "invalid Cardid: $cardid . update data/carddata.json from https://cardbase.skylords.eu/Cards/GetCards";
        }

        return new SkylordsCardDetail(array("cardId"=>$cardid, "cardName"=>"unknown_card_$cardid"));

    }

    function recreateFromCardbase(){

        $this->loadCardsFromCardbase();

        file_put_contents($this->localPath, json_encode($this->cards));
        file_put_contents($this->localPathPretty, json_encode($this->cards,JSON_PRETTY_PRINT));

        echo "done";

    }

}