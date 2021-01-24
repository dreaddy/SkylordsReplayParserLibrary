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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replaydeck.inc.php");

class CardsPlayedDetail{
    public $cardid;
    public $upgrade;
    public $played;
    public $name;
    public $playedOn;
}

class SkylordsDeckPlayer{


    /**
     * Name of the Player as saved in replay (16 bit per char wstring)
     * @var string
     */
    public $name_wstring;

    /**
     * Name of the Player
     * @var string
     */
    public $name;



    /**
     * Group of Player. "Real" Players (non NPC) are always in team 4 and 5. See "teams" in replaydata
     * @var int
     */
    public $group_id;

    /**
     * Position in the current Group (in a 2v2 pvp it is 0,1 )
     * @var int
     */
    public $id_in_group;

    /**
     * Type of the player. Should be 1 for all non npc players
     * @var int
     */
    public $type;

    /**
     * Cards in deck as stored in replay (should be count ($deck) )
     * @var mixed
     */
    public $cardcount;


    /**
     * saved in replay... seems to always 0?
     * @var mixed
     */
    public $cardcount_hi;

    /**
     * Summary of $cardsPlayed
     * @var int[]
     */
    public $cardsPlayed;

        /**
     * Cards played with more detailled Infos
     * @var CardsPlayedDetail[]
     */
    public $cardsPlayed_detail;



    /**
     * Has player left the game by pressing quit. Does not cover a defeat by loosing stuff
     * @var bool
     */
    public $leftGame;

    /**
     * actions per Minute
     * @var int
     */
    public $apm;

    /**
     * last Action made by this playerstarting from the beginning of the replay. used for apm. Unit is in 10 units per second (600 is 1 min)
     * @var int
     */
    public $last_action;

    /**
     * Overall Actions done by this player. used for apm.
     * @var int
     */
    public $actioncount = 0;

    /**
     * played Orbs reconstructed by played cards
     * @var array
     */
    public $orbs;

    function getOrbsString(){

        if(!isset($this->orbs["in_order"]))return "";

            $retval="";
        $first=true;
        foreach($this->orbs["in_order"] as $orbcolorstring){

            if($first)$first=false;
            else $retval.=", ";

            $retval.=$orbcolorstring;
        }

        return $retval;

    }

    function getOrbHtml($color){

        $colorcodesOrbs = array(ORB_FIRE=>"#ff0000", ORB_FROST=>"#0000ff", ORB_NATURE=>"#00ff00", ORB_SHADOW=>"6A0DAD", ORB_NEUTRAL=>"#ffffff");

        $retval = "<div class='orb' style='box-shadow:0 4px 8px 0 rgba(0, 0, 0, 0.3), 0 6px 20px 0 rgba(0, 0, 0, 0.29);;margin:3px;border-radius:45px;border:1px solid black;background-color:".$colorcodesOrbs[$color].";display: inline-block;width:20px;height:20px;'></div>";

        return $retval;

    }

    function getOrbsHtml(){

        if(!isset($this->orbs["in_order"]))return "";

        $retval="";
        $first=true;
        foreach($this->orbs["in_order"] as $orbcolorstring){

            if($first)$first=false;



            $retval.=$this->getOrbHtml($orbcolorstring);
        }

        return $retval;

    }

    /**
     * Get Deck of Player as a String. It appears to be the wrong deck sometimes so its better to use getDeckFromPlayedCards
     * maybe the deck is filled with the client deck the replay if from because it is unknown to the game.
     * @return string
     */
    function getDeckString(){

        if(!isset($this->deck))return "";

        $retval="";
        $first=true;
        foreach($this->deck->deckcards as $deckcard){

            if($first)$first=false;
            else $retval.=", ";

            $usedString="";

            if(isset($this->cardsPlayed[$deckcard->getCarddata(true)->cardId]))
                $usedString = "(used ".$this->cardsPlayed[$deckcard->getCarddata(true)->cardId]." "."times)";

            $retval.=$deckcard->getCarddata(true)->cardName.$usedString;

        }

        return $retval;


    }




     /**
     * Get Deck of Player as a String from played Cards. It appears getDeckString gives the wrong deck sometimes (see fyre example replay) so its better to use getDeckFromPlayedCards
     * @return string
     */
    function getDeckHtmlFromPlayedCards(){

        if(!isset($this->deck))return "";

        $retval="<div class='deckwrap' >";
        $first=true;
        foreach($this->cardsPlayed_detail as $cardid=>$deckcardinfo){

            $timesUsed = $deckcardinfo["played"];
            $upgrade = $deckcardinfo["upgrade"];
            $retval.="<div class='cardwrap' style='display:inline-block;width:120px;vertical-align:top;'>";
            $cardinfo = SkylordsCardbase::getInstance()->getCardById($cardid);
            $cardinfodetails = SkylordsCardbaseDetails::getInstance()->getCardById($cardid);

            $retval.="<p style='text-align:center;font-weight:bold;min-height:38px;'>".$cardinfo->cardName."</p>";

            if(empty($cardinfodetails)){
                if(SL_DEBUG)
                echo "Warning: carddetails not set:".$cardid;;
            }


            if(empty($cardinfodetails->Image->Url)){
                if(SL_DEBUG)
                echo "Warning: carddetailsimage not set:".$cardid;
            }

            if($first)$first=false;


            $usedString="";

            if(!empty($cardinfodetails)){
                $retval.="<img style='width:120px' src='".CARDIMG_BASE_URI.$cardinfodetails->Image->Url."'>";
            }

            $usedString .= "(Upg:{$upgrade} Used:{$timesUsed}x)";

            $retval .= $usedString;
            $retval.="</div>";
        }
        $retval.="</div>";
        return $retval;


    }

    /**
     * Get Deck of Player as a String from played Cards. It appears getDeckString gives the wrong deck sometimes (see fyre example replay) so its better to use getDeckFromPlayedCards
     * @return string
     */
    function getDeckStringFromPlayedCards(){

        if(!isset($this->deck))return "";

        $retval="";
        $first=true;
        foreach($this->cardsPlayed as $cardid=>$timesUsed){

            if($first)$first=false;
            else $retval.=", ";

            $usedString="";

            $usedString = "(ID $cardid, used ".$timesUsed." "."times)";

            $retval .= SkylordsCardbase::getInstance()->getCardById($cardid)->cardName.$usedString;

        }

        return $retval;


    }
    /**
     * Actions done by this player ordered by typeid (number)
     * @var int[]
     */
    public $actionsByTypeid;

    /**
     * Actions done by this player ordered by typeid (string)
     * @var int[]
     */
    public $actionsByType;

    /**
     * ID for this player used for replayactions
     * @var int
     */
    public $actionplayerid;





}