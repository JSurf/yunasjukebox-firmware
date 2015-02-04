<?php
require "vendor/autoload.php";
require "config.php";

$stdin = fopen('php://stdin','r');

$mpd = new mpd($config["mpdserver"], 6600);
//TODO configurable max volume
$mpd->SetVolume(95);
$mpd->Disconnect();

if ($stdin) {
  echo "Waiting for RFID scan...";
  while(($line = fgets($stdin)) !== false) {
     echo "Got ".$line."\n";
     
     $line = trim($line);
     file_put_contents($config["mpdpath"]."/rfidlast",$line);
     
     $mpd = new mpd($config["mpdserver"], 6600);
     $mpd->Stop();
     $mpd->PLClear();
     $mpd->PLAdd($line);
     exec("gpio write 1 1");
     $mpd->Play();
     exec("gpio write 2 1");
     exec("gpio write 3 1");
     
     print_r($mpd);
     
     $mpd->Disconnect();            
  }
}
