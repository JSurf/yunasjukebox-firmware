#!/bin/sh
tar cvf /var/www/backup.tar --exclude=/var/www/backup.tar --exclude=dev --exclude=sys --exclude=proc --exclude=var/lib/mpd/music / 

