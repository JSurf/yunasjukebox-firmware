<?php
require "vendor/autoload.php";
require "config.php";
while (true) {
   echo "Waiting on interrupt for playpause\n";
   exec("gpio wfi 27 falling");
   exec("gpio write 25 0");     
   $mpd = new mpd($config["mpdserver"], 6600);
   if ($mpd->state === "play") {
      echo "Switch to pause state\n";
      $mpd->Pause();
   } else {
      echo "Switch to play state\n";
      exec("gpio write 1 1");
      $mpd->Play();
      exec("gpio write 2 1");
      exec("gpio write 3 1");
   }
   $mpd->Disconnect();
   sleep(1);
   exec("gpio write 25 1");
}
