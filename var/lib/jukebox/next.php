<?php
require "vendor/autoload.php";
require "config.php";
while (true) {
   echo "Waiting on interrupt for previous\n";
   exec("gpio wfi 4 falling");
   exec("gpio write 2 0");
   $mpd = new mpd($config["mpdserver"], 6600);
   echo "Switch to previous track\n";
   $mpd->Next();
   $mpd->Disconnect();
   sleep(1);
   exec("gpio write 2 1");
}
