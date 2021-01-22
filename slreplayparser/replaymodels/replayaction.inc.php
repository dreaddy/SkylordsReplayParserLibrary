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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."../cardbase/card.inc.php");

/**
 * Replayactions are how the replay is built after the header. Alway contain timestamp, size and command. Rest depends  on the replaycommand
 */
class SkylordsReplayAction{

    /**
     * Name of $action
     * @var string
     */
    public $action;

    /**
     * id of action
     * @var int
     */
    public $actionid;

    /**
     * replaytime in 10 units per second (600 is 1 min)
     * @var string
     */
    public $time;

    /**
     * parameters as key val array depending on action
     * @var array
     */
    public $data;


   /**
    * Summary of __construct
    * @param string $action action as string
    * @param int $actionid action as id
    * @param int $time time in sec/100
    * @param array $data parameters as key val array depending on action
    */
    function __construct($action, $actionid, $time, $data){

        $this->action = $action;
        $this->actionid = $actionid;
        $this->time = $time;
        $this->data = $data;

   }


}