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
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."card.inc.php");

/**
 * cardloader for data from carddata.json or https://auctions.backend.skylords.eu/api/cards/all (skylords marketplace)
 * Not used any more because the cardbase api has more details (like images)
 * Used to assign the correct Carddata for the playerdecks and actions
 */
class SkylordsCardbase{

    function __construct(){
        $this->loadCards();
    }

    private static $instance = null;



    /**
     * Summary of getInstance
     * @return SkylordsCardbase
     */
    static function getInstance(){

        if(empty(SkylordsCardbase::$instance)){
            SkylordsCardbase::$instance = new SkylordsCardbase();
        }

        return SkylordsCardbase::$instance;

    }

    /**
     * official apilink for the carddata
     * @var string
     */
    public $remoteUrl = "https://auctions.backend.skylords.eu/api/cards/all";

    /**
     * local json carddata
     * @var string
     */
    public $localPath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."carddata.json";


    /**
     * Summary of $cards
     * @var SkylordsCard[]
     */
    public $cards = array();

    function loadCards($remote = false){


        $content_json = null;

        if($remote){
            $content_json = file_get_contents($this->remoteUrl);
        }else{
            $content_json = file_get_contents($this->localPath);
        }

        $content = json_decode($content_json,false, 512, JSON_THROW_ON_ERROR);

        $this->cards=array();



        foreach($content as $data){
            $this->cards[$data->cardId]=new SkylordsCard($data);
        }


    }

    /**
     * retrieve Carddata of specific card. To get the id used here use sl_extractCardkey
     * @param int $cardid
     * @return SkylordsCard
     */
    function getCardById($cardid){

        if(isset($this->cards[$cardid])){
            return $this->cards[$cardid];
        }

        if(SL_DEBUG){
            echo "invalid Cardid: $cardid . update data/carddata.json from https://auctions.backend.skylords.eu/api/cards/all";
        }

        return new SkylordsCard(array("cardId"=>$cardid, "cardName"=>"unknown_card_$cardid"));

    }


    function getCardByName($cardname, $ignoreIds=array(), $isPromo = false){



        foreach($this->cards as $card){
            if(isset($ignoreIds[$card->cardId]))continue;
            if($card->cardName == $cardname){
                if($card->promo == "Yes" && $isPromo){
                    return $card;
                }
                else if($card->promo == "No" && !$isPromo){
                    return $card;
                }
            }
        }


        if(SL_DEBUG){
            echo "invalid Cardname: $card . update data/carddata.json from https://auctions.backend.skylords.eu/api/cards/all";
        }

        return new SkylordsCard(array("cardId"=>-1, "cardName"=>"unknown_card_$cardname"));

    }

}