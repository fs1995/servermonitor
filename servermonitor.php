<?php
/*
Plugin Name: ServerMonitor
Plugin URI: https://github.com/fs1995/servermonitor/
Description: A simple plugin to view server resource usage (ram, cpu, disk), check your PHP error log, and more.
Author: Francis Smith
Version: 0.3.6
Author URI: https://github.com/fs1995
License: GPL2
*/

defined('ABSPATH') or die('I\'m just a WordPress plugin. Not much I can do when run directly.');

//check if we are on a Linux platform.
$is_linux = 0;
if (PHP_OS === "Linux")
  $is_linux = 1;
if(!$is_linux){ //If not on Linux
  delete_option('servermonitor_update_interval'); //then cleanup db entry,
  exit("Currently ServerMonitor only supports the Linux Platform. Your detected platform is: " . PHP_OS . ". You can request support for this platform in the plugin support forum, and if I get enough requests, it may be added in a future update."); //and prevent plugin from activating.
}

function servermonitor_readlog($file){
  if(file_exists($file)){
    if(filesize($file) > '0'){
      if(is_readable($file)){
        return "Reading file: ".$file."<hr>".file_get_contents($file);
      }else{
        return "Error: The file ".$file." is not readable. Please report this <a href=\"https://wordpress.org/support/plugin/servermonitor\" target=\"_blank\">here</a>.";
      }
    }else{
      return "The file ".$file." is empty.";
    }
  }else{
    return "Error: The file ".$file." does not exist. Please report this <a href=\"https://wordpress.org/support/plugin/servermonitor\" target=\"_blank\">here</a>.";
  }
}

add_action('admin_menu', 'servermonitor_menu'); //hook into WP menu
add_action('wp_ajax_servermonitor_monitorajax', 'servermonitor_monitorajax'); //ajax request handler

function servermonitor_menu(){ //create the plugins menu
  add_menu_page('ServerMonitor', 'ServerMonitor', 'manage_options', 'servermonitor',  'servermonitor_monitor', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMCAzMCI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTExLjkyIDMuMzhMOC44OCA5LjI1bC0yLjQgNC43N0gzLjMxYy0yLjg2IDAtMy4zMS4yMS0zLjMxIDEuMTIgMCAxLjAxLjQ4IDEuMiAzLjQ3IDEuMiAyLjU4IDAgNC4xMy4wNSA0LjMyLS4xOS4xNi0uMTYgMS4zNi0yLjY5IDIuNTYtNC45YTIwLjMyIDIwLjMyIDAgMCAxIDIuMTMtMy45MmwxLjI1IDYuNTMgMS43NCA5LjA3Yy43NCAzLjk0IDEuMDYgNC43NyAxLjYgNC43Ny4xMyAwIC41Ni0uNDUgMS4wOS0xLjM5LjUzLS45MyAxLjI4LTIuNDUgMi4xOS00LjMyTDIzIDE2LjM0bDMuMi0uMDNjMy4zNC0uMDIgMy43OS0uMSAzLjc5LTEuMTQgMC0xLjA3LS40LTEuMDctNC4wNS0xLjA3LTIuNTYgMC0zLjY4LS4xMy00LjA4LjEzLS4yNy4yMi0xLjIgMi0yLjE0IDMuODdhMzUuODIgMzUuODIgMCAwIDEtMiA0LjE5Yy0uMSAwLS4zMi0xLjM0LS40Mi0xLjk4LS4xNi0uOTgtLjg2LTQuNjEtMi45MS0xNS4xMi0uNS0yLjY2LS42Ny0zLjA2LTEuMTUtMy4xNC0uNDItLjA2LS43Mi4yNC0xLjMgMS4zM3oiLz48L3N2Zz4=');
  add_submenu_page ('servermonitor', 'Server Resource Monitor', 'Resource Monitor', 'manage_options', 'servermonitor', 'servermonitor_monitor');
  add_submenu_page ('servermonitor', 'System Information', 'System Info', 'manage_options', 'servermonitor-info', 'servermonitor_info');
  add_submenu_page ('servermonitor', 'Clear cache', 'Clear cache', 'manage_options', 'servermonitor-cache', 'servermonitor_cache');
  add_submenu_page ('servermonitor', 'PHP error log', 'PHP error log', 'manage_options', 'servermonitor-php', 'servermonitor_php');

  add_action('admin_init', 'register_servermonitor_settings');
}

function servermonitor_monitor(){ //generate the resource monitor page
  require 'monitor.php'; //in a separate file cause theres a bit to this page.
  wp_enqueue_style('servermonitor-chartistcss', plugins_url('css/chartist.min.css', __FILE__) );
  wp_enqueue_style('servermonitor-monitorcss', plugins_url('css/monitor.css', __FILE__), array('servermonitor-chartistcss') );
  wp_enqueue_script('servermonitor-chartistjs', plugins_url('js/chartist.min.js', __FILE__) );
  wp_enqueue_script('servermonitor-smoothiejs', plugins_url('js/smoothie.min.js', __FILE__) );
  wp_enqueue_script('servermonitor-monitorjs', plugins_url('js/monitor.js', __FILE__), array('servermonitor-chartistjs', 'jquery') );
}

function servermonitor_info(){ //generate the resource monitor page
  if(isset($_POST['servermonitor-phpinfo'])){
    echo phpinfo();
    exit;
  }
  $servermonitor_uptime = floatval(file_get_contents('/proc/uptime')); //read uptime
  $servermonitor_uptime_secs = round(fmod($servermonitor_uptime, 60), 0); $servermonitor_uptime = (int)($servermonitor_uptime / 60);
  $servermonitor_uptime_mins = $servermonitor_uptime % 60; $servermonitor_uptime = (int)($servermonitor_uptime / 60);
  $servermonitor_uptime_hr = $servermonitor_uptime % 24; $servermonitor_uptime = (int)($servermonitor_uptime / 24);
  $servermonitor_uptime_days = $servermonitor_uptime;

  echo "<div class=\"wrap\"><h1>System Information</h1>Hostname: ", gethostname(), "<br>Uptime: ", $servermonitor_uptime_days, " days, ", $servermonitor_uptime_hr, " hours, ", $servermonitor_uptime_mins, " minutes, ", $servermonitor_uptime_secs, " seconds.<br>Server IP: ", $_SERVER['SERVER_ADDR'], "<br>PHP version: ", phpversion(), "<br>Platform: ", PHP_OS,  "<br><br></div><form method=\"post\" action=\"\"><input type=\"submit\" name=\"servermonitor-phpinfo\" value=\"View phpinfo()\" /></form>";
}

function servermonitor_cache(){
  echo "<div class=\"wrap\"><h1>Clear cache</h1>If changes are not showing up, you may need to clear cache.<br><br><form method=\"post\" action=\"\">";

  if(isset($_POST['servermonitor-static'])){ //delete static cache button was clicked
    function servermonitor_filecache($dir){
      if(is_dir($dir)){ //if we are given a directory...
        $objects = scandir($dir); //list all files/dirs within it...
        foreach ($objects as $object){
          if ($object != "." && $object != "..") {
            if(is_dir($dir."/".$object)){ //if we come across a directory within it...
              servermonitor_filecache($dir."/".$object); //will need to remove all files within that dir.
            }else{
              unlink($dir."/".$object); //else, delete the individual files.
              echo "Deleted file: ". $dir."/".$object."<br>";
            }
          }
        }
        rmdir($dir); //we are done with the foreach object loop and have an empty directory, delete it.
        echo "Removed directory: ".$dir."<br>";
      }
    }

    servermonitor_filecache(WP_CONTENT_DIR."/cache");
    echo "Done clearing file cache!<br>";
  }

  if(isset($_POST['servermonitor-opcache'])){ //flush opcache button was clicked
    wp_cache_flush();
    if(opcache_reset()){ //reqs php 5.5+
      echo "OpCache cleared!<br>";
    }else{
      echo "Error: opcode cache seems to be disabled.<br>";
    }
  }

  echo "<br><input type=\"submit\" name=\"servermonitor-static\" value=\"Delete static file cache\" /> Delete contents of wp-content/cache/<br><br>";
  echo "<input type=\"submit\" name=\"servermonitor-opcache\" value=\"Clear opcode cache\" /> Flush PHP OpCache<br><br></form></div>";
  echo "Your browser could be caching too: <a href=\"https://www.liquidweb.com/kb/clearing-your-browser-cache/\" target=\"_blank\">how to clear browser cache</a>";
}

function servermonitor_php(){ //generate the php error log page
  echo "<div class=\"wrap\"><h1>PHP Error Log viewer</h1>This page does not automatically update, you will need to refresh it. If you are troubleshooting WordPress code, have you turned on <a href=\"https://codex.wordpress.org/Debugging_in_WordPress\" target=\"_blank\">WP_DEBUG</a> in wp-config.php?</div><pre>". servermonitor_readlog(ini_get('error_log')) ."</pre>";
} //passing the servermonitor_readlog function the php error log location, which will return the contents of the log for this function to display

function register_servermonitor_settings(){ //register the plugins settings
  register_setting('servermonitor-settings-group', 'servermonitor_update_interval', 'absint');
}

function servermonitor_monitorajax(){
  //global $wpdb; //provides access to db
  //$test = intval( $_POST['test'] );
  require 'api.php';
  wp_die(); //terminate immediately and return response
}
