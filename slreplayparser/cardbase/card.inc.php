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

define('CARDTYPE_SHADOW', "Shadow");
define('CARDTYPE_FROST', "Frost");
define('CARDTYPE_FIRE', "Fire");
define('CARDTYPE_NATURE', "Nature");
define('CARDTYPE_STONEKIN', "Stonekin");
define('CARDTYPE_BANDIT', "Bandit");
define('CARDTYPE_TWILIGHT', "Bandit");
define('CARDTYPE_NEUTRAL', "Neutral");
define('CARDTYPE_LOSTSOUL', "Lost Soul");
define('CARDTYPE_AMII', "Amii");

define('ORB_SHADOW', "Shadow");
define('ORB_FROST', "Frost");
define('ORB_FIRE', "Fire");
define('ORB_NATURE', "Nature");
define('ORB_NEUTRAL', "Neutral");

/**
 * containerclass for json data from carddata.json / https://auctions.backend.skylords.eu/api/cards/all
 */
class SkylordsCard{

    /**
     * "official" Cardid as used in most places. Use function sl_extractCardkey to get this cardid from the replay cardid
     * @var int
     */
    public $cardId;

    /**
     * Summary of $cardName
     * @var string
     */
    public $cardName;

    /**
     * Common, Uncommon, Rare, Ultra Rare
     * @var string
     */
    public $rarity;

    /**
     * Twilight, Renegade, Lost Souls, Amii
     * @var string
     */
    public $expansion;

    /**
     * Summary of $promo
     * @var string
     */
    public $promo;

    /**
     * Summary of $obtainable
     * @var string
     */
    public $obtainable;

    /**
     * Summary of $fireOrbs
     * @var int
     */
    public $fireOrbs;

    /**
     * Summary of $frostOrbs
     * @var int
     */
    public $frostOrbs;

    /**
     * Summary of $natureOrbs
     * @var int
     */
    public $natureOrbs;

    /**
     * Summary of $shadowOrbs
     * @var int
     */
    public $shadowOrbs;

    /**
     * Summary of $neutralOrbs
     * @var int
     */
    public $neutralOrbs;



    /**
     * Shadow, Fire, Frost, Nature, Stonekin, Neutral, Bandit, Twilight, Lost Soul
     * @var string
     */
    public $cardType;



    /**
     * get amount of orbs (tier level) needed as int
     * @return float
     */
    public function getOrbSum(){

        return $this->frostOrbs+$this->fireOrbs+$this->natureOrbs+$this->shadowOrbs+$this->neutralOrbs;

    }

    /**
     * amount of colored orbs as an int
     * @return int
     */
    public function getColoredOrbSum(){

        return $this->frostOrbs+$this->fireOrbs+$this->natureOrbs+$this->shadowOrbs;

    }

    /**
     * amount of colorless orbs as an int
     * @return int
     */
    public function getNeutralOrbSum(){

        return $this->neutralOrbs;

    }


    public function getColorsUsed(){

        $retval = array();

        if(!empty($this->natureOrbs))$retval["Nature"]=$this->natureOrbs;
        if(!empty($this->frostOrbs))$retval["Frost"]=$this->frostOrbs;
        if(!empty($this->fireOrbs))$retval["Fire"]=$this->fireOrbs;
        if(!empty($this->shadowOrbs))$retval["Shadow"]=$this->shadowOrbs;
        if(!empty($this->shadowOrbs))$retval["Neutral"]=$this->neutralOrbs;

        return $retval;

    }

    /**
     * Summary of __construct
     * @param mixed $data data-array containing the values to set as key=>val
     */
    function __construct($data = null){

        if($data != null){

            foreach($data as $key=>$val){

                $this->$key = $val;

            }
        }

    }

}