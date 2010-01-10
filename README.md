This module allows monitoring of hosts, by running a process on each monitored
host which updates an entry in a shared database table on a periodic basis (by
default once every 45 seconds). If possible, the entry in the database table
for the row will include a JSON-encoded block of data which contains:

* The current load average and number of logged-in users
* Details of mounted filesystems (parsed from 'df')
* The structure returned by uname(2)

Check out git://github.com/nexgenta/heartbeat.git into your app/ directory.

You will also need to configure the cluster module, and ensure your
configurations match across all of the hosts you want to monitor.

To configure, you will need to define HEARTBEAT_IRI in your config.php, and add
the following to your appconfig.php:

	$SETUP_MODULES[] = array('name' => 'heartbeat', 'file' => 'module.php', 'class' => 'HeartbeatModule');
	$CLI_ROUTES['heartbeat'] = array('class' => 'HeartbeatCLI', 'name' => 'heartbeat', 'file' => 'cli.php', 'description' => 'Launch the heartbeat process');

Note that HEARTBEAT_IRI should be specified with “autoconnect=no” in the
parameter list to ensure proper handling of transient errors (for example,
loss of network connectivity).

Once done, you can run './eregansu setup' to update the database schema.

You can launch the heartbeat process by running './eregansu heartbeat'.

The heartbeat process will use HOST_NAME if defined, falling back to
INSTANCE_NAME otherwise. If neither is explicitly defined, the cluster
module defines INSTANCE_NAME to be the first component of the system
nodename as reported by uname. If the host you are monitoring runs
multiple instances (for example, a staging instance and a live instance),
then you should define HOST_NAME explicitly. Similarly, if for
some reason you have defined INSTANCE_NAME explicitly but the instance name
is not the same as the hostname used in the cluster configuration,
then you will need to define HOST_NAME explicitly.

In other words, the hostname used by the heartbeat module (whether it’s
taken from HOST_NAME, INSTANCE_NAME or derived from the system nodename) needs
to match the hostname specified in the cluster configuration.

To enable filesystem logging, you must define POSIX_DF_CMDLINE in your
config.php to be the complete command-line which produces the equivalent
output of POSIX-specified 'df -P -k'. For example, on Mac OS X:

	define('POSIX_DF_CMDLINE', '/bin/df -P -k');

On Solaris, it would be:

	define('POSIX_DF_CMDLINE', '/usr/xpg4/bin/df -P -k');

Other systems will vary. If POSIX_DF_CMDLINE is not defined, filesystem
logging will not occur.


To enable logging of the load average and number of logged-in users, you
must define UPTIME_PATH:

	define('UPTIME_PATH', '/usr/bin/uptime');


To change the pulse interval, define HEARTBEAT_PULSE_INTERVAL to the number of
seconds which should elapse between pulses. The default value for
HEARTBEAT_PULSE_INTERVAL is 45.
