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

define("CARDIMG_BASE_URI", "https://cardbase.skylords.eu");

/**
 * containerclass for json data from carddata.json / https://cardbase.skylords.eu/Cards/GetCards
 */
class SkylordsCardDetail{

        public $cardId;
        public $Name;

        public $Rarity;
        public $Cost;

        public $Edition;
        public $Type;

        public $Color;
        public $Affinity;

        public $IsRanged;
        public $Defense;
        public $Offense;
        public $DefenseType;
        public $OffenseType;
        public $UnitCount;
        public $ChargeCount;
        public $Category;

        public $Abilities;
        public $Upgrades;

        public $OrbInfo;
        public $extra;
        public $Image;


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