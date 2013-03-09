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
