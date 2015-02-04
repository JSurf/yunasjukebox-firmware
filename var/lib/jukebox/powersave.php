<?php
require "vendor/autoload.php";
require "config.php";

$lastplay = microtime(true);
while (true) {
   $mpd = new mpd($config["mpdserver"], 6600);
   if ($mpd->state === "play") {
      $lastplay = microtime(true);
   } else {
      $stoptime = microtime(true) - $lastplay;
      echo "Stopped since ".($stoptime)."\n";
      if ($stoptime >= 290 && $stoptime < 310) {
         // Switch off amp after 5 min
         echo "5 minutes no activity. Switching off amplifier\n";
         exec("gpio write 1 0");
         exec("gpio write 2 0");
         exec("gpio write 3 0");
      } else if ($stoptime > 3600) {
         echo "One hour without activity...Shutdown\n";
         exec("poweroff");
      }
   }
   $mpd->Disconnect();
   sleep(20); 
}
