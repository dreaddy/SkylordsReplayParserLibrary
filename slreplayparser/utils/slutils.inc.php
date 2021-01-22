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


/*
 *  mixed utility functions and definitions required for the skylords parser.
 *  Read Bytes, extract key etc.
 *  prefix of all functions is sl_
 */


define("SL_TEAM_1", 4);
define("SL_TEAM_2", 5);

define("ACTION_MOVE", 0xFAD);
define("ACTION_ATTACK", 0xFAF);
define("ACTION_DESTROY", 0xFA7);
define("ACTION_SUMMON", 0xFA9);
define("ACTION_SPELL", 0xFAA);
define("ACTION_LINESPELL", 0xFAB);
define("ACTION_BUILD", 0xFAC);
define("ACTION_LEAVEGAME", 0xFA2);
define("ACTION_CREATE_WALL", 0xFBD);
define("ACTION_DESTRUCT", 0xFC9);
define("ACTION_CREATE_ORB", 0xFBF);
define("ACTION_CREATE_MANA", 0xFBE);
define("ACTION_USE_UNIT_ABILITY", 0xFAE);
define("ACTION_STOP_COMMAND", 0xFB3);
define("ACTION_HOLD_COMMAND", 0xFB4);

// seems to be constant, so no need to read it out of the replay
define("TM_ANIMAL", 2);
define("TM_CREEP", 3);
define("TM_NEUTRAL", 1);
define("TM_TEAM1", 4);
define("TM_TEAM2", 5);

$_sl_byteReadCount = 0;
$_sl_byteReadCount_started=false;
/**
 * Summary of sl_byteReadCount_add
 * @param int $count
 */
function sl_byteReadCount_add($count){
    global $_sl_byteReadCount,$_sl_byteReadCount_started;
    if($_sl_byteReadCount_started){
        $_sl_byteReadCount+=$count;
    }
}

function sl_byteReadCount_start(){
    global $_sl_byteReadCount,$_sl_byteReadCount_started;
    $_sl_byteReadCount=0;
    $_sl_byteReadCount_started=true;
}

function sl_byteReadCount_get(){
    global $_sl_byteReadCount;
    return $_sl_byteReadCount;
}

function sl_byteReadCount_get_clean(){
    $retval = sl_byteReadCount_get();
    sl_byteReadCount_start();
    return $retval;
}

function sl_getShortArrayFromString($string){

    return array_values(unpack('v*', $string)); // s oder v? N oder V


}

function sl_readUInt64($handle){
    $sizeToRead = 8;
    sl_byteReadCount_add($sizeToRead);
    $bytes = fread( $handle, $sizeToRead);
    return unpack("P",$bytes)[1];
}

function sl_readUInt32($handle){
    $sizeToRead = 4;
    sl_byteReadCount_add($sizeToRead);
    $bytes = fread( $handle, $sizeToRead);
    return unpack("V",$bytes)[1];
}

function sl_readUInt16($handle){
    $sizeToRead = 2;
    sl_byteReadCount_add($sizeToRead);
    $bytes = fread( $handle, $sizeToRead);
    return unpack("v",$bytes)[1];
}

function sl_readUInt8($handle){
    $sizeToRead = 1;
    return current(sl_readBytes($handle, $sizeToRead));
}

function sl_readInt8($handle){
    $sizeToRead = 1;
    return current(sl_readBytes($handle, $sizeToRead))-256/2;
}

function sl_seekUntilString($needle, $handle, $searchlimit = -1, $seekToEnd = false){

    $oldpos = ftell($handle);

    $lastchunk = "";
    $filestring = "";

    while( ($searchlimit == -1 || strlen($filestring)<=$searchlimit) && !feof($handle)){


        $lastchunk = fread($handle, 100);
        $filestring.= $lastchunk;

        $strpos = strpos($filestring, $needle);
        if($strpos !== false){
            fseek($handle, $strpos + ($seekToEnd?strlen($needle):0) );
            return $strpos;
        }

    }

    fseek($handle, $oldpos);
    return false;

}

function sl_readWstring($handle, $sizelimit = -1){

    //$bytes = fread( $handle, 4);
    $length = sl_readUInt32($handle); //unpack("V",$bytes)[1];
    if($sizelimit != -1 && $sizelimit < $length){ // prevents crashes if not reading a wstring on this position but trash
        $length = $sizelimit;
    }
    $string = fread( $handle, $length*2);
    return $string;

}


function sl_readString($handle, $sizelimit = -1){

    //$bytes = fread( $handle, 4);
    $length = sl_readUInt32($handle); //unpack("V",$bytes)[1];
    if($sizelimit != -1 && $sizelimit < $length){ // prevents crashes if not reading a wstring on this position but trash
        $length = $sizelimit;
    }
    $string = fread( $handle, $length);
    return $string;

}


function sl_getByteArrayFromString($string){

    return array_values(unpack('C*', $string));

}

function sl_readBytes($handle, $length){

    $sizeToRead = $length;
    sl_byteReadCount_add($sizeToRead);
    return sl_getByteArrayFromString(fread($handle, $sizeToRead));
}

function sl_extractCardkey($filekey){
    return $filekey % 0x4240;
}

function sl_extractCardUpgrade($filekey){
    return (int)($filekey / 0x4240);
}

function sl_printBytes($bytearray, $printchar=true, $printposition=true){
    foreach($bytearray as $id=>$byte){

        echo "$byte";

        if($printchar){
            echo "(".chr($byte).")";
        }

        if($printposition){
            echo "(".$id.")";
        }


        echo " ";
    }

}