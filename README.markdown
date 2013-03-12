# OpenStatus
## About
OpenStatus is a server monitoring system based on [scrd](https://github.com/DimeCadmium/scrd) and nikkiii's [status page](https://github.com/nikkiii/status)

## Installation

### Debian

Add the following repository to your /etc/apt/sources.list file:

```deb http://deb.nickmoeck.com/debian/ stable main```

Then download my key:

```wget -O- http://deb.nickmoeck.com/debian/packages.gpg.key | apt-key add -```

Then:

``` apt-get update && apt-get install openstatus-server```

Replace openstatus-server with openstatus-client for client machines.


### Other distros
 - copy openstatus-server, the "web" directory, and openstatus.db to your server
 - copy openstatus-client to your clients
 - copy and edit the configuration files (default location is /etc/openstatus/)
 - put the contents of "web" directory somewhere that your webserver can serve them from
 - edit config.php to point to the location of openstatus.db
 - ensure that the database file and its parent directory are owned by the user PHP scripts run as, and that that user can read and write to the database
file.
 - start openstatus-server and openstatus-client
  - openstatus-server -c \<config file\>
  - openstatus-client -c \<config file\>
  - \<config file\> defaults to /etc/openstatus/openstatus-[client|server].conf

## Possible Problems
### High Disk Usage
If you're experiencing disk I/O problems with openstatus-server, it may be because the database has grown too large.  To alleviate this problem, you can delete some of the old data from the OpenStatus database file.
Steps:
 - Open the OpenStatus database with the ```sqlite3``` program (You may have to install it from your distro's repositories). 
 - Run the following queries to delete history data from more than an hour ago: 
   - ```DELETE FROM history WHERE time < ((SELECT strftime('%s','now') - 3600));```
   - ```DELETE FROM history5 WHERE time < ((SELECT strftime('%s','now') - 3600));```
   - ```DELETE FROM history10 WHERE time < ((SELECT strftime('%s','now') - 3600));```
 - If you have a large number of alerts, you may wish to delete some old alerts as well.  The following query will do that:
   - ```DELETE FROM alerts WHERE alert_time < ( (SELECT strftime('%s','now') - 3600));```
 - Your database may not have proper indexes created.  Old versions of OpenStatus did not create any indexes, and they may not be created during updates.  The following indexes should help:
   - ```CREATE INDEX history_index ON history (uid);```
   - ```CREATE INDEX history_time_index ON history (time);```
   - ```CREATE INDEX alert_index ON alerts (server_uid, alert_time, alert_acked);```
