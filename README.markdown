# OpenStatus
## About
OpenStatus is a server monitoring system based on [scrd](https://github.com/DimeCadmium/scrd) and nikkiii's [status page](https://github.com/nikkiii/status)

## Installation
For Debian and its derivatives, see [this page](http://www.nickmoeck.com/openstatus/) forinstall instructions.  For other distros:

 - copy the contents of openstatus-server to your server
 - copy the contents of openstatus-client to your clients
 - edit the configuration files (located in etc/openstatus)
 - put the contents of usr/share/openstatus-server/ somewhere that your webserver can serve them from
 - edit config.php to point to the location of openstatus.db
 - ensure that the database file and its parent directory are owned by the user PHP scripts run as, and that that user can read and write to the database
file.
 - start openstatus-server and openstatus-client
  - openstatus-server -c <config file>
  - openstatus-client -c <config file>
  - <config file> defaults to /etc/openstatus/openstatus-[client|server].conf
