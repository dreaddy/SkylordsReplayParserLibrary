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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replayteam.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replayaction.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replaydeckcard.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."../cardbase/card.inc.php");


/**
 * Contains all the data from the replay
 */
class SkylordsReplayData{



   /**
    * detect the winner of the game. In theory this does not always work (although it does in most cases)
    * @return int
    */
   function detectWinner(){

       $team1 = $this->getPlayerByGroup(SL_TEAM_1);
       $team2 = $this->getPlayerByGroup(SL_TEAM_2);

       // pve map, always return id of playing team
       if(empty($team1))return SL_TEAM_2;
       if(empty($team2))return SL_TEAM_1;

       // all player of a team left the game. Winner is the other team
       if($this->countPlayerLeft($team1)==count($team1)){
           return SL_TEAM_2;
       }
       if($this->countPlayerLeft($team2)==count($team2)){

           return SL_TEAM_1;
       }

       // this works in most cases: player that executed the last commands is the winner
       if($this->lastActionInTeam($team1)>$this->lastActionInTeam($team2)){
           return SL_TEAM_1;
       }else{
           return SL_TEAM_2;
       }

   }


     /**
    * Summary of CountPlayerLeft
    * @param SkylordsDeckPlayer[] $team
    */
   function lastActionInTeam($team){

       $retval = 0;

       foreach($team as $p){
           if($p->last_action > $retval){
               $retval = $p->last_action;
           }
       }

       return $retval;
   }

   /**
    * Summary of CountPlayerLeft
    * @param SkylordsDeckPlayer[] $team
    */
   function countPlayerLeft($team){

       $retval = 0;

       foreach($team as $p){
           if($p->leftGame){
               $retval++;
           }
       }

       return $retval;

   }

   function getWinnerteamString(){

       $winnerteam = $this->getPlayerByGroup($this->winnerteam);

       if(empty($winnerteam))return "unknown";

       $retval = "";

       $first=true;
       foreach($winnerteam as $winnermember){
           if(!$first)$retval.=", ";

           if($first)$first=false;

           $retval .= $winnermember->name;

       }

       return $retval;

   }

   /**
   * Actions are how replays are saved (after the header). Contains all Actions of all Players
   * @var SkylordsReplayAction[]
   */
   var $actions;

   /**
    * Name of the map
    * @var string
    */
   var $mapname;


   /**
    * Summary of $teams
    * @var SkylordsTeam
    */
   var $winnerteam;


   /**
    * Teams in this game. Includes only NPC Factions (creeps, neutral, animal)
    * @var SkylordsTeam[]
    */
   var $npcteams;

   /**
    * Teams in this game. Includes only Players
    * @var SkylordsTeam[]
    */
   var $teams;

   /**
    * Summary of $version
    * @var string
    */
   var $fileversion;

   /**
    * Summary of $filename
    * @var string
    */
   var $filename;

   /**
    * Summary of $players
    * @var SkylordsDeckPlayer[]
    */
   var $players;

   function isInNpcGroup($playerdata){
       if(empty($playerdata->deck->deckcards))return true;
       return false;/*

       echo $groupid;

       $players = $this->getPlayerByGroup($groupid);
       var_dump($players);
       foreach($players as $player){
           if(empty($player->deck))return false;
       }


       foreach($this->teams as $team){
           if($team->id == $groupid)
           {
               return false;
           }
       }

       return true;*/
   }

   function getPlayerByGroup($groupid){

       $group=array();

       foreach($this->players as $player){

           if($player->group_id == $groupid){

               $group[]=$player;

           }

       }

       return $group;

   }

   /**
    * Length of the Replay in 10 units per Second ( 600 = 1 min )
    * @var int
    */
   var $replay_length = 0;

   /**
    * get replaylength in seconds
    * @return float
    */
   function getReplayLengthMinutes(){
       return floor($this->replay_length/600);
   }

   /**
    * get replaylength in seconds
    * @return int
    */
   function getReplayLengthSeconds(){
       return ((int)($this->replay_length/10));
   }

   /**
     /** Create Timestring m:s of replaylength
     * @return string
    */
   function getReplayTimeString(){
       return $this->getReplayLengthMinutes().":".($this->getReplayLengthSeconds()%60);
   }

   /**
    * Sets data from actionlog to the correct player
    * @param mixed $n
    * @param mixed $playerdata
    */
   function setDataForNthPlayer($n, $playerdata){



       foreach($this->players as $key=>$entry){

           //echo "key is $key";
          // echo count($this->players)." $n ";

           if($entry->group_id != SL_TEAM_1 && $entry->group_id != SL_TEAM_2){
               continue;  // npc player, ignore
           }

           if($key == $n){
              // echo "setting $n";

               //$this->players[$key]->actionsdata = $playerdata;

               //$this->players[$key]->actionplayerid=$playerdata["actionplayerid"];

               foreach($playerdata as $pkey=>$pval){
                   //echo $this->players[$key]->name;
                   $this->players[$key]->$pkey=$pval;
               }

           }


       }


   }



}