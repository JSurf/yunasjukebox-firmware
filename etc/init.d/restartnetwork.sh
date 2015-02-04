#!/bin/sh
#
# Restart network resources....
#
sleep 10
/etc/init.d/S40network restart
ifup eth0
/etc/init.d/S50dropbear restart
/etc/init.d/S85mongoose restart
exit 0
