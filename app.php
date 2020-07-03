<?php

require_once 'vendor/autoload.php';

use PhpTabs\PhpTabs;
use PhpTabs\Component\Config;

use PhpTabs\Music\Beat;
use PhpTabs\Music\Channel;
use PhpTabs\Music\Duration;
use PhpTabs\Music\Measure;
use PhpTabs\Music\MeasureHeader;
use PhpTabs\Music\Note;
use PhpTabs\Music\NoteEffect;
use PhpTabs\Music\TabString;
use PhpTabs\Music\Tempo;
use PhpTabs\Music\TimeSignature;
use PhpTabs\Music\Track;
use PhpTabs\Music\Voice;

Config::set("debug", true);

$songName = "gone";
$str = file_get_contents($songName.".json");
$songIn = json_decode($str, true);
// var_dump($songIn["c"]);

$tabOut = new PhpTabs();
$songOut = $tabOut->getSong();
$songOut->setName($songIn["title"]);
$songOut->setArtist($songIn["artist"]);
$songOut->setAlbum($songIn["album"]);
$songOut->setAuthor("csguth");
$songOut->setDate("23/02/2019");

function makeDuration($obj) {
    $durationOut = new Duration();
    $durationOut->setValue($obj["value"]);
    $durationOut->setDotted($obj["isDotted"]);
    $durationOut->setDoubleDotted($obj["isDoubleDotted"]);
    $durationOut->getDivision()->setEnters($obj["tuplet"]["enters"]);
    $durationOut->getDivision()->setTimes($obj["tuplet"]["times"]);
    return $durationOut;
}

foreach ($songIn["measureHeaders"] as &$measureHeaderIn) {
    $measureHeaderOut = new MeasureHeader();
    $measureHeaderOut->setNumber($measureHeaderIn["number"]);
    $measureHeaderOut->setStart($measureHeaderIn["start"]);
    $timeSignatureOut = new TimeSignature();
    $timeSignatureOut->setNumerator($measureHeaderIn["timeSignature"]["numerator"]);
    $denominatorOut = makeDuration($measureHeaderIn["timeSignature"]["denominator"]);
    $timeSignatureOut->setDenominator($denominatorOut);
    $measureHeaderOut->setTimeSignature($timeSignatureOut);
    $tempoOut = new Tempo();
    $tempoOut->setValue($measureHeaderIn["tempo"]);
    $measureHeaderOut->setTempo($tempoOut);
    if ($measureHeaderIn["tripletFeel"] === 0)
    {
        $measureHeaderOut->setTripletFeel(MeasureHeader::TRIPLET_FEEL_NONE);
    }
    $measureHeaderOut->setRepeatAlternative($measureHeaderIn["repeatAlternative"]);
    $songOut->addMeasureHeader($measureHeaderOut);
}

foreach ($songIn["tracks"] as &$trackIn) {

    $trackOut = new Track();
    $trackOut->setName($trackIn["name"]);

    foreach ($trackIn["strings"] as &$stringIn) {
        $trackOut->addString(new TabString($stringIn["number"], $stringIn["value"]));
    }

    foreach ($trackIn["measures"] as $key => &$measureIn) {
        $measureOut = new Measure($songOut->getMeasureHeaders()[$key]);

        foreach ($measureIn["beats"] as &$beatIn) {
            $beatOut = new Beat();
            $beatOut->setStart($beatIn["start"]);

            foreach ($beatIn["voices"] as &$voiceIn) {
                $voiceOut = $beatOut->getVoice($voiceIn["index"]);
                $voiceOut->setDirection($voiceIn["direction"]);
                $voiceOut->setEmpty($voiceIn["isEmpty"]);
                $durationOut = makeDuration($voiceIn["duration"]);

                foreach ($voiceIn["notes"] as &$noteIn) {
                    $noteOut = new Note();
                    $noteOut->setString($noteIn["string"]);
                    $noteOut->setValue($noteIn["value"]);
                    $noteOut->setVelocity($noteIn["velocity"]);
                    $noteOut->setTiedNote($noteIn["isTiedNote"]);
                    $noteEffectOut = new NoteEffect();
                    $noteEffectOut->setVibrato($noteIn["effect"]["vibrato"]);
                    $noteEffectOut->setSlide($noteIn["effect"]["slide"]);
                    $noteEffectOut->setAccentuatedNote($noteIn["effect"]["accentuatedNote"]);
                    $noteEffectOut->setHeavyAccentuatedNote($noteIn["effect"]["heavyAccentuatedNote"]);
                    $noteOut->setEffect($noteEffectOut);
                    $voiceOut->addNote($noteOut);
                }

                $voiceOut->setDuration($durationOut);
            }

            $measureOut->addBeat($beatOut);
        }

        $trackOut->addMeasure($measureOut);
    }

    $channelIn = $trackIn["channel"];
    $channelOut = $songOut->getChannelById($channelIn["channel"]);
    if ($channelOut === null) {
        $channelOut = new Channel();
        $channelOut->setName($trackIn["name"]);
        if ($trackIn["isPercussionTrack"]) {
            $channelOut->setBank(Channel::DEFAULT_PERCUSSION_BANK);    
        }
        $channelOut->setChannelId($channelIn["channel"]);
        $channelOut->setVolume($channelIn["volume"]);
        $channelOut->setBalance($channelIn["balance"]);
        $channelOut->setChorus($channelIn["chorus"]);
        $channelOut->setReverb($channelIn["reverb"]);
        $channelOut->setPhaser($channelIn["phaser"]);
        $channelOut->setTremolo($channelIn["tremolo"]);
        $channelOut->setProgram($channelIn["_instrument"]);
        $songOut->addChannel($channelOut);
    } else {
        if ($channelOut->getVolume() != $channelIn["volume"]) {
            echo $channelOut->getVolume() . ", " . $channelIn["volume"] . "\n";
        }
        if ($channelOut->getBalance() != $channelIn["balance"]) {
            echo $channelOut->getBalance() . ", " . $channelIn["balance"] . "\n";
        }
        assert($channelOut->getChorus() === $channelIn["chorus"]);
        assert($channelOut->getReverb() === $channelIn["reverb"]);
        assert($channelOut->getPhaser() === $channelIn["phaser"]);
        assert($channelOut->getTremolo() === $channelIn["tremolo"]);
    }
    $trackOut->setChannelId($channelOut->getChannelId());
    $trackOut->setSong($songOut);
    $songOut->addTrack($trackOut);
    // break;
}

// echo $songOut->getRenderer('ascii')->render(1);
// $songOut->save('rose.ascii');
echo $tabOut->save($songName.".gp4");

?>
