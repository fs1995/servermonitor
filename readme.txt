=== ServerMonitor ===
Contributors: fs1995
Donate link: https://paypal.me/fs1995
Tags: server info, system monitor, disk usage, disk space, memory, php error log, information, debug, monitor, phpinfo
Requires at least: 3.4
Tested up to: 4.9.5
Requires PHP: 5.1.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to view server resource usage (ram, cpu, disk), check your PHP error log, and more.

== Description ==

View your PHP error log, CPU and RAM usage, and view disk space all in one location. This is a new plugin under development. Currently it does not do terribly much, but we are constantly working on adding new and useful features.

Why use this plugin instead of the many others? ServerMonitor does not use PHP's shell_exec, which is disabled by many web hosts for security concerns. Currently this plugin only supports Linux servers. Windows Server support is planned, but is a low priority.

For any bug reports or suggestions, let me know in the plugins support forum.

== Screenshots ==

1. View resource usage in real time.
2. View the PHP error log.
3. Clear cache easily.

== Changelog ==

= 0.3.6 =
*Release Date - Apr 6, 2018*

* Initial release. This plugin is a copy of my LW MWP Tools plugin, but with the platform specific features of that plugin removed, and new features to come soon.
* Don't hardcode PHP error log location.
* View phpinfo().
