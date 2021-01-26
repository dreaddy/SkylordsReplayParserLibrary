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
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."utils/slutils.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cardbase/cardbase.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cardbasedetails/cardbasedetails.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replaymodels/replaydata.inc.php");

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."replaymodels/replaydeckplayer.inc.php");

// debugmode prints problems found like invalid cardid, wrong actionsize. Set to true before including the replayparser

if(!defined("SL_DEBUG"))
    define('SL_DEBUG', false);

class SkylordsReplayParser{


   private $filename;

   /**
    * Filename (with full path) of Replay
    */
   public function setReplayfilename($filename){

       $this->filename = $filename;
   }

   /**
    * Summary of __construct
    * @param string $filename  Filename (with full path) of Replay
    */
   function __construct($filename){

       $this->filename = $filename;


   }

    /**
    * Load All Data from the Replay specified in "filename". Use loadHeaderData to read Headerinfos only (faster, but many infos are missing)
    * @throws Exception
    * @return SkylordsReplayData
    */
   public function loadData(){
       $replaydata = $this->loadHeaderData();



       $replaydata->actions = $this->loadActions($replaydata);

       $replaydata = $this->calculateAdditionalInfosFromActions($replaydata);

       return $replaydata;

   }

   /**
    * Calculate Winner, cards played etc. and set into replaydata
     * @param SkylordsReplayData $replaydata
    */
   public function calculateAdditionalInfosFromActions($replaydata){

       $actionplayer = array();


       foreach($replaydata->actions as $action){

           // first action found for player
           if(isset($action->data["who"])){

               $player = $action->data["who"];

               if(!isset($actionplayer[$player])){

                   $actionplayer[$player] = array(
                       "actionplayerid"=>$player,
                       "actioncount"=>0,
                       "actionsByType"=>array(),
                       "actionsByTypeid"=>array(),
                       "cardsPlayed"=>array(),
                       "apm"=>0,
                       "last_action"=>0,
                       "orbs"=>array(ORB_FIRE=>0, ORB_FROST=>0, ORB_SHADOW=>0, ORB_NATURE=>0, ORB_NEUTRAL=>0, "in_order"=>array()),
                       "leftGame"=>false,
                       "enlightenment_played_before"=>false);

               }

               $actionplayer[$player]["actioncount"]++;

               if($action->time > $replaydata->replay_length){
                   $replaydata->replay_length = $action->time;
               }

               if($action->time > $actionplayer[$player]["last_action"]){
                   $actionplayer[$player]["last_action"] = $action->time;
               }

               if($action->actionid == ACTION_LEAVEGAME){
                   $actionplayer[$player]["leftGame"]=true;
               }

               if(!isset($actionplayer[$player]["actionsByType"][$action->action])){
                   $actionplayer[$player]["actionsByType"][$action->action]=0;
               }
               $actionplayer[$player]["actionsByType"][$action->action]++;

               if(!isset($actionplayer[$player]["actionsByTypeid"][$action->actionid])){
                   $actionplayer[$player]["actionsByTypeid"][$action->actionid]=0;
               }
               $actionplayer[$player]["actionsByTypeid"][$action->actionid]++;

               // spell / building / unit is casted

               if(isset($action->data["cast"]) && $action->data["cast"] == true){

                   $cardid = sl_extractCardkey($action->data["card"]);

                   if(!isset($actionplayer[$player]["cardsPlayed"][$cardid])){

                       $card = SkylordsCardbase::getInstance()->getCardById($cardid);

                       $actionplayer[$player]["cardsPlayed"][$cardid]=0;
                       $actionplayer[$player]["cardsPlayed_detail"][$cardid]=array("cardid"=>$cardid, "upgrade"=>sl_extractCardUpgrade($action->data["card"]), "played"=>0, "name"=>$card->cardName, "playedOn"=>array());



                       if(!$actionplayer[$player]["enlightenment_played_before"])
                       {
                           $actionplayer[$player]["orbs"] = $this->expandOrbsUsed($card->fireOrbs, $card->frostOrbs, $card->natureOrbs, $card->shadowOrbs, $card->neutralOrbs, $actionplayer[$player]["orbs"]);
                       }else{
                           $actionplayer[$player]["enlightenment_played_before"]=false;
                       }

                       if($cardid == 968){
                           $actionplayer[$player]["enlightenment_played_before"]=true;
                       }


                   }

                   $actionplayer[$player]["cardsPlayed"][$cardid]++;
                   $actionplayer[$player]["cardsPlayed_detail"][$cardid]["played"]++;
                   $actionplayer[$player]["cardsPlayed_detail"][$cardid]["playedOn"][]=$action->time;


               }


           }



       }

       ksort($actionplayer);
       $count=0;
       foreach($actionplayer as $key=>$playerdata){

           $playerdata["apm"] = round($playerdata["actioncount"]/($playerdata["last_action"]/600), 3);
           $playerdata["orbs"]["sum"] = $playerdata["orbs"][ORB_FIRE]+$playerdata["orbs"][ORB_FROST]+$playerdata["orbs"][ORB_NEUTRAL]+$playerdata["orbs"][ORB_SHADOW]+$playerdata["orbs"][ORB_NATURE];
           while($playerdata["orbs"]["sum"]>count($playerdata["orbs"]["in_order"])){
               $playerdata["orbs"]["in_order"][]=ORB_NEUTRAL;
           }
           unset($playerdata["enlightenment_played_before"]); // flag for calculations, no use in data


           if(empty($playerdata["orbs"]["sum"]))continue; // npc player that did some stuff and messes up the replay
           if(empty($playerdata["cardsPlayed"]))continue; // npc player that did some stuff and messes up the replay

           $replaydata->setDataForNthPlayer($count, $playerdata);

           $count++;
       }


       $replaydata->winnerteam = $replaydata->detectWinner();

       return $replaydata;

   }


   function expandOrbsUsed($fire, $frost, $nature, $shadow, $neutral, $orbs){

       $orbsAdded = 0;



       if($fire>$orbs[ORB_FIRE]){
           $neworbs=$fire-$orbs[ORB_FIRE];
           $orbsAdded+=$neworbs;
           $orbs[ORB_FIRE]=$fire;
           for($i=0;$i<$neworbs;$i++){
               $orbs["in_order"][]=ORB_FIRE;
           }

       }

       if($frost>$orbs[ORB_FROST]){
           $neworbs=$frost-$orbs[ORB_FROST];
           $orbsAdded+=$neworbs;
           $orbs[ORB_FROST]=$frost;
           for($i=0;$i<$neworbs;$i++){
               $orbs["in_order"][]=ORB_FROST;
           }
       }

       if($nature>$orbs[ORB_NATURE]){

           $neworbs=$nature-$orbs[ORB_NATURE];
           $orbsAdded+=$neworbs;
           $orbs[ORB_NATURE]=$nature;
           for($i=0;$i<$neworbs;$i++){
               $orbs["in_order"][]=ORB_NATURE;
           }
       }

       if($shadow>$orbs[ORB_SHADOW]){

           $neworbs=$shadow-$orbs[ORB_SHADOW];
           $orbsAdded+=$neworbs;
           $orbs[ORB_SHADOW]=$shadow;
           for($i=0;$i<$neworbs;$i++){
               $orbs["in_order"][]=ORB_SHADOW;
           }
       }

       if($orbsAdded >0){
           $orbs[ORB_NEUTRAL]-=$orbsAdded;
           if($orbs[ORB_NEUTRAL]<0)$orbs[ORB_NEUTRAL]=0;
       }

       $orbssum = $orbs[ORB_NEUTRAL]+$orbs[ORB_SHADOW]+$orbs[ORB_NATURE]+$orbs[ORB_FROST]+$orbs[ORB_FIRE];
       $cardorbssum = $fire+$frost+$nature+$shadow+$neutral;

       if($orbssum<$cardorbssum){
           $orbs[ORB_NEUTRAL]+=$cardorbssum-$orbssum;
       }

       return $orbs;

   }



   /**
     * Read all Actions in the Replay. header_size_until_actions must be set to the beginning of the actions (done by reading the header)
    * @param SkylordsReplayData $replaydata
    * @throws Exception
    * @return SkylordsReplayAction[]
    */
   private function loadActions($replaydata){

        if(!file_exists($this->filename)){
            throw new Exception("file not existing: ".$this->filename);
        }

        $fp = fopen($this->filename, "r");

        //echo "starting from ".$replaydata->header_size_until_actions." ";

        fseek($fp, $replaydata->header_size_until_actions);

        //echo "now at '".ftell($fp)."' ";

       // $buffer = sl_readBytes($fp, 500);
      //  var_dump($buffer);

       // return $replaydata;

        $actions = array();

        $maxSize = filesize($this->filename);

        while(!feof($fp)){

            $remaining = $maxSize - ftell($fp);
            if($remaining<=0)break;

            $valid = true;
            $time = sl_readUInt32($fp); if(feof($fp))break;
            $size = sl_readUInt32($fp); if(feof($fp))break;

            $data = array("type"=>"empty_command");
            // echo "reading on ".ftell($fp)."<br>";
            if($size>0){

                sl_byteReadCount_start();


                $typeid = sl_readUInt32($fp);

                $data = array();

                if($size > 1000 || $typeid > 0xfff || $typeid < 0xf00){  // ignore invalid commands and try again one byte later
                    //throw new Exception("<br> replaycommand on parsing action $typeid ($size bytes on time $time) -> size must be invalid, replay damaged? pos: ".ftell($fp));
                    fseek($fp, ftell($fp)-11);// fallback -> move forward 1 byte and retry
                    $valid=false;
                }else{


                    switch($typeid){


                        case ACTION_SUMMON:
                            $data = array(
                            "type"=>"summon_unit",
                            "cast"=>true,
                            "card"=>sl_readUInt16($fp),
                            "cardx"=>sl_readUInt16($fp),
                            "who"=>sl_readUInt32($fp),
                            "byte"=>sl_readUInt8($fp),
                            "cardc"=>sl_readUInt16($fp),
                            "cardcx"=>sl_readUInt16($fp),
                            "charges"=>sl_readUInt8($fp),
                            "x"=>sl_readUInt32($fp),  // todo: real not uint
                            "y"=>sl_readUInt32($fp),  // todo: real not uint
                            "zero"=>sl_readUInt32($fp));


                            if($size-sl_byteReadCount_get() == 30){
                                sl_readBytes($fp, 30); // TODO: some attack,move and summon unit commands are 30 bytes larger - why?
                            }



                            break;

                        case ACTION_ATTACK:
                            $data = array(
                              "type"=>"attack",
                              "who"=>sl_readUInt32($fp),
                              "unit_count"=>$this->readUnits(sl_readUInt16($fp), $fp),
                              "unit"=>$this->_buffer,
                              "data"=>sl_readBytes($fp, 5),
                              "value1"=>sl_readUInt32($fp),
                              "value2"=>sl_readUInt32($fp),
                              "target"=>sl_readUInt32($fp),
                              "x"=>sl_readUInt32($fp), #todo: real not uint
                              "y"=>sl_readUInt32($fp), #todo: real not uint
                              "byte"=>sl_readBytes($fp, 1)  // todo: something useful?
                              );

                            if($size-sl_byteReadCount_get() == 30){
                                sl_readBytes($fp, 30); // TODO: some attack,move and summon unit commands are 30 bytes larger - why?
                            }
                            break;

                        case ACTION_MOVE:
                            $data = array(
                            "type"=>"move",
                            "who"=>sl_readUInt32($fp),
                            "unit_count"=>$this->readUnits(sl_readUInt16($fp), $fp),
                            "unit"=>$this->_buffer,
                            "position_count"=>$this->readPositions(sl_readUInt16($fp), $fp),
                            "position"=>$this->_buffer,
                            "bytes"=>sl_readBytes($fp, 6)  // todo: something useful?
                            );
                            if($size == 60){
                                sl_readBytes($fp, 30); // TODO: some attack,move and summon unit commands commands are 60. why? error in readunits/readpositions with multiple units?
                            }
                            if($size-sl_byteReadCount_get() == 12){
                                sl_readBytes($fp, 12); // TODO: some attack,move commands are 12 bytes larger - why?
                            }

                            break;

                        case ACTION_CREATE_MANA:
                            $data = array(
                              "type"=>"create_mana_well",
                              "who"=>sl_readUInt32($fp),
                              "unit"=>sl_readUInt32($fp));
                            break;


                        case ACTION_USE_UNIT_ABILITY:
                            $data = array(
                             "type"=>"unit_ability",
                             "who"=>sl_readUInt32($fp),
                             "unit"=>sl_readUInt32($fp),
                             "card"=>sl_readUInt16($fp),
                             "cardx"=>sl_readUInt16($fp),
                             "bytes"=>sl_readBytes($fp, 5), // todo: something useful?
                             "duration"=>sl_readUInt32($fp),
                             "zero"=>sl_readUInt32($fp),
                             "target"=>sl_readUInt32($fp),
                             "x"=>sl_readUInt32($fp), #todo: real not uint
                             "y"=>sl_readUInt32($fp) #todo: real not uint
                             );
                            break;


                        case ACTION_CREATE_ORB:
                            $data=
                              array(
                              "type"=>"create_orb",
                              "who"=>sl_readUInt32($fp),
                              "unit"=>sl_readUInt32($fp),
                              "color"=>sl_readUInt8($fp)
                              );
                            break;

                        case ACTION_SPELL:
                            $data=
                                array(
                            "type"=>"cast_spell",
                            "cast"=>true,
                            "card"=>sl_readUInt16($fp),
                            "cardx"=>sl_readUInt16($fp),
                            "who"=>sl_readUInt32($fp),
                            "byte"=>sl_readBytes($fp,1),
                            "cardc"=>sl_readUInt16($fp),
                            "cardcx"=>sl_readUInt16($fp),
                            "charges"=>sl_readUInt8($fp),
                            "bytes"=>sl_readBytes($fp,5),
                            "value"=>sl_readUInt32($fp),
                            "zero"=>sl_readUInt32($fp),
                            "target"=>sl_readUInt32($fp),
                            "x"=>sl_readUInt32($fp),  // todo: real not uint
                            "y"=>sl_readUInt32($fp));  // todo: real not uint
                            break;

                        //   case 4043: // PVE Command. Could be creation of manawells/transfer ownershop on twilight encounter


                        case ACTION_CREATE_WALL:
                            $data=
                              array(
                              "type"=>"create_wall",
                              "who"=>sl_readUInt32($fp),
                              "unit"=>sl_readUInt32($fp),
                              "anotherinfo"=>sl_readUInt8($fp)
                              );
                            break;

                        case 4028: // ACTION_TOGGLE_WALL_GATE
                            $data=
                              array(
                              "type"=>"toggle_wall_gate",
                              "who"=>sl_readUInt32($fp),
                              "value"=>sl_readUInt32($fp)
                              );
                            break;

                        case ACTION_LINESPELL:
                            $data=
                              array(
                          "type"=>"cast_linespell",  // like wall of fire
                          "cast"=>true,
                          "card"=>sl_readUInt16($fp),
                          "cardx"=>sl_readUInt16($fp),
                          "who"=>sl_readUInt32($fp),
                          "charges"=>sl_readUInt8($fp),
                          "cardc"=>sl_readUInt16($fp),
                          "cardcx"=>sl_readUInt16($fp),
                          "bytes"=>sl_readBytes($fp,18),
                          "x"=>sl_readUInt32($fp),  // todo: real not uint
                          "y"=>sl_readUInt32($fp),  // todo: real not uint
                          "bytes2"=>sl_readBytes($fp,12),
                          "x2"=>sl_readUInt32($fp),  // todo: real not uint
                          "y2"=>sl_readUInt32($fp));  // todo: real not uint
                            break;

                        case ACTION_LEAVEGAME:
                            $data=
                              array(
                              "type"=>"leave_game",
                              "who"=>sl_readUInt32($fp)
                              );
                            break;

                        /*  case 4007: // game ended? TODO: verify
                        $data=
                        array(
                        "type"=>"game_ended"
                        );
                        break; */

                        case ACTION_DESTROY: // destroy? todo: verify

                            $data=
                        array(
                          "type"=>"destroy",  // destroy unit, guessed, verify!
                          "who"=>sl_readUInt32($fp),
                          "unit_count"=>$this->readUnits(sl_readUInt16($fp), $fp),
                          "unit"=>$this->_buffer);
                            break;

                        case ACTION_HOLD_COMMAND:
                            $data=
                        array(
                          "type"=>"hold_position",
                          "who"=>sl_readUInt32($fp),
                          "unit_count"=>$this->readUnits(sl_readUInt16($fp), $fp),
                          "unit"=>$this->_buffer);
                            break;

                        case ACTION_STOP_COMMAND:

                            $data=
                        array(
                          "type"=>"stop_unit",
                          "who"=>sl_readUInt32($fp),
                          "unit_count"=>$this->readUnits(sl_readUInt16($fp), $fp),
                          "unit"=>$this->_buffer);
                            break;

                        case ACTION_BUILD: // Cast Building
                            $data=
                                array(
                            "type"=>"cast_building",
                            "cast"=>true,
                            "card"=>sl_readUInt16($fp),
                            "cardx"=>sl_readUInt16($fp),
                            "who"=>sl_readUInt32($fp),
                            "cardb"=>sl_readUInt16($fp),
                            "cardbx"=>sl_readUInt16($fp),
                            "x"=>sl_readUInt32($fp),  // todo: real not uint
                            "z"=>sl_readUInt32($fp),
                            "y"=>sl_readUInt32($fp),  // todo: real not uint
                            "zero"=>sl_readUInt32($fp),
                            "cardc"=>sl_readUInt16($fp),
                            "cardcx"=>sl_readUInt16($fp),
                            "charges"=>sl_readUInt8($fp));
                            break;

                        default:

                            if($size != 0){
                                $buffer = fread($fp, $size-4);
                                sl_byteReadCount_add($size-4);

                                  // fseek to next
                                $data = array(
                               "type"=>"unknown_command_".$typeid,
                               "datasize"=>$size,
                               "data"=>$data);
                            }



                    }

                }

            }

            if($valid && isset($data["type"]) && $data["type"] != "empty_command"){

                $newaction = new SkylordsReplayAction($data["type"], $typeid, $time, $data); //array("time"=>$time, "size"=>$size, "typeid"=>$typeid, "data"=>$data);

                $actions[]=$newaction;



               /* echo "<br>";
                echo "<br>";

                var_dump($newaction);*/

            }else{
                if(SL_DEBUG){
                    echo "Invalid command, skipping: ".print_r($newaction, true)."<br>";
                }
            }

            // Warning Command summon_unit: size is 30 but 32 bytes were read - fixing
            if(sl_byteReadCount_get() != $size && $size < 500){
                $cmdtypeString = $newaction->data["type"];

                if(SL_DEBUG){
                    echo "Warning Command $cmdtypeString: size is $size but ".sl_byteReadCount_get()." bytes were read - autofixing<br>";
                }

                $bytesDifference = $size-sl_byteReadCount_get();

               // echo "moving filepointer:".$bytesDifference;
               // echo " from ".ftell($fp)." ";

                fseek($fp,  ftell($fp)+$bytesDifference);

               // echo " to ".ftell($fp)." <br>";

            }

        }

        echo "<hr>";

        $data = array();

        return $actions;
    }


   private $_buffer = null;

   /**
    * Read Unityids of Actioncommand
    * @param mixed $count
    * @param mixed $fp
    * @return mixed
    */
   private function readUnits($count, $fp){


       $this->_buffer=array();

       for($i=0;$i<$count;$i++){ // TODO: something is wrong here. info is not that important, ignore for now
          // $this->_buffer[$i] = array("unitid"=>sl_readUInt32($fp));
       }

       return $count;

   }

   /**
    * Read Positions in Actioncommand
    * @param mixed $count
    * @param mixed $fp
    * @return mixed
    */
   private function readPositions($count, $fp){

       $this->_buffer=array();

       for($i=0;$i<$count;$i++){ // TODO: something is wrong here. info is not that important, ignore for now
         // $this->_buffer[$i] = array("x"=>sl_readUInt32($fp), "y"=>sl_readUInt32($fp));
       }

       return $count;

   }

    /**
     * Load Header Data from the Replay specified in "filename". Only about 5% of the File and is therefore much faster but only contains basic Informations like Playername and Deck. No Winner, No apm, no Actions and calculated Values from Actions
     * @throws Exception
    * @return SkylordsReplayData
    */
   public function loadHeaderData(){


       if(!file_exists($this->filename)){
           throw new Exception("file not existing: ".$this->filename);
       }

       $filetime = filemtime($this->filename);

       $fp = fopen($this->filename, "r");
      // $contents = fread($fp, filesize($this->filename));

       $extension = fread($fp, 3);
       if($extension != "PMV"){
           throw new Exception("not a valid replayfile");
       }


       $replaydata = new SkylordsReplayData();

       $replaydata->filename = $this->filename;
       $replaydata->fileversion = sl_readUInt32($fp);

       $replaydata->gameversion = 0;
       if($replaydata->fileversion > 200)$replaydata->gameversion = sl_readUInt32($fp);

       $replaydata->playtime = sl_readUInt32($fp);

       if($replaydata->fileversion > 213) // $replaydata->header_new_dummy =
       {
         $replaydata->some_stuff_added_after_v_213 = sl_readUInt32($fp);
       }
      // echo "version is ". $replaydata->fileversion;

       $replaydata->filetime = $filetime;

        // next is filetime. can be retrieved with filemtime instead I think
       //$trash = fread($fp, 15);


       $replaydata->mapfilename = sl_readString($fp, 500);// name of the map. Also includes type

      /* $mapnamechunk = fread($fp, 150);
       $replaydata->filename = substr($mapnamechunk,0,strpos($mapnamechunk, ".map")+4);
       fseek($fp,0);
       $startpos = sl_seekUntilString( ".map", $fp, -1, true );*/

       $replaydata->mapname = trim(pathinfo($replaydata->mapfilename)["filename"]);

       // always in windows format, problems on linux servers, so do it manually again if possible
       $last_backslash_in_mapname_pos = strrpos($replaydata->mapfilename, "\\");

       if($last_backslash_in_mapname_pos !== false){
           $replaydata->mapname=substr($replaydata->mapfilename, $last_backslash_in_mapname_pos);
       }

       $replaydata->mapname_with_metainfos=$replaydata->mapname;

       $last_underline_in_mapname_pos = strrpos($replaydata->mapname, "_");
       if($last_underline_in_mapname_pos !== false){
           $replaydata->mapname=substr($replaydata->mapname, $last_underline_in_mapname_pos + 1);

       }

        $replaydata->mapname=str_replace(".map", "", $replaydata->mapname);
        $replaydata->mapname=str_replace("\\randommap/", "", $replaydata->mapname);

        $replaydata->maptype = $replaydata->getTypeFromMapname();

       $startpos = sl_seekUntilString( ".map", $fp, -1, true );

       $replaydata->header_size_until_actions = sl_readUInt32($fp)+ftell($fp);
       $replaydata->unknown_data1 = sl_readUInt8($fp);
       $replaydata->unknown_data2 = sl_readUInt8($fp);
       $replaydata->v_7 = sl_readUInt32($fp); // ??

       $replaydata->difficulty = sl_readUInt8($fp); // TODO: verify how the playercount changes this number - I think on rpve i have to substract the playernumber


       $replaydata->V_0200 = sl_readUInt16($fp);
       $replaydata->hosterplayer_id = sl_readUInt64($fp);
       $replaydata->group_count = sl_readUInt8($fp);
       $replaydata->matrix_length = sl_readUInt16($fp);
       $replaydata->matrix = array();

       for($matrix_count=0; $matrix_count<$replaydata->matrix_length; $matrix_count++){
           $replaydata->matrix[] = array("i"=>sl_readUInt8($fp), "j"=>sl_readUInt8($fp), "v"=>sl_readUInt8($fp));
       }
       // contains who is allied to which player. Don't think it's required for us

       $replaydata->teams = array();
       $replaydata->npcteams = array();

       $replaydata->teams_length = sl_readUInt16($fp);

       for($team_count=0; $team_count<$replaydata->teams_length; $team_count++){


               $newteam =
               new SkylordsTeam(sl_readString($fp, 200), sl_readUInt32($fp), sl_readUInt16($fp));

               if(!$newteam->isNpc){
               $replaydata->teams[] = $newteam;
               }else{
                   $replaydata->npcteams[] = $newteam;
               }
       }

       $replaydata->players = array();
       $replaydata->npcplayers = array();

       while(ftell($fp)<$replaydata->header_size_until_actions){

           $playerinfo = new SkylordsDeckPlayer();
           $playerinfo->name_wstring = sl_readWstring($fp, 200);
           $playerinfo->name = preg_replace('/[[:cntrl:]]/', '', $playerinfo->name_wstring);

           $playerinfo->playerid = sl_readUInt64($fp);
           $playerinfo->group_id = sl_readUInt8($fp);
           $playerinfo->id_in_group = sl_readUInt8($fp);
           $playerinfo->type = sl_readUInt8($fp);
           $playerinfo->cardcount = sl_readUInt8($fp);
           $playerinfo->cardcount_hi = sl_readUInt8($fp);



           $deckcards = array();
           for($cardnum=0;$cardnum<$playerinfo->cardcount;$cardnum++){

               $deckcard = new SkylordsDeckCard(null);

               $deckcard->deckcardid = sl_readUInt16($fp);
               $deckcard->cardid = $deckcard->getOfficialCardid();
               $deckcard->cardname = $deckcard->getCarddata()->cardName;
               $deckcard->cardupgrade = sl_readUInt16($fp)/15; // 15 30 45
               $deckcard->cardcharges = sl_readUInt8($fp);
               /*$deckcard->cardid = $deckcard->getOfficialCardid();
               $deckcard->upgrade = $deckcard->getUpgradeFromDeckcardId();
               $deckcard->carddata = $deckcard->getCarddata();*/

               $deckcards[$cardnum]=$deckcard;

           }

           $playerinfo->deck = new SkylordsDeck($deckcards);

           if(!$replaydata->isInNpcGroup($playerinfo))
               $replaydata->players[] = $playerinfo;
           else
               $replaydata->npcplayers[] = $playerinfo;

       }

       if($replaydata->maptype == "RPVE")
       {
           $replaydata->difficulty-=count($replaydata->players); // TODO: test and verify
       }

       fclose($fp);

       return $replaydata;

   }

}