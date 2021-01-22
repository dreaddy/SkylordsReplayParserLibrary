# SkylordsReplayParserLibrary
PHP Library to retrieve Informations from Replays of the Skylords Reborn Game

It contains almost anything that can be read out of the replays and some calculated Values (decks, apm, winner, teams, monuments used, cards used, every action done by the players etc. ).

Might be useful for an own replay archive, automatic reports for tourneys or just to spy out what deck the rpve Player used that was 10x faster than you ;) ).

A quick demo script also with all values extracted:
http://torsten-lueders.de/replayapi/ 

Just call $replaydataobject = new SkylordsReplayParser("pathandnameofmyreplay.map")->loadData();
The example above is in replaytest.php.
