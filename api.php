<?php defined('ABSPATH') or die('I\'m just a WordPress plugin. Not much I can do when run directly.');

switch($_POST['action']){ //get the WP ajax action to call the appropriate function
  case "servermonitor_monitorajax":
    resource_monitor();
    break;
  default:
    header("HTTP/1.0 404 Not Found"); //invalid action
    break;
}

function resource_monitor(){
  ##### LOAD AVERAGES #####
  $loadavg = sys_getloadavg();
  $load_1 = number_format($loadavg[0], 2);
  $load_5 = number_format($loadavg[1], 2);
  $load_15 = number_format($loadavg[2], 2);
  #########################

  ##### NUMBER OF CORES #####
  preg_match_all('/^processor/m', file_get_contents('/proc/cpuinfo'), $cores);
  $cores = count($cores[0]);
  ###########################

  ##### RAM/SWAP INFO #####
  $meminfo = preg_split('/\ +|[\n]/', file_get_contents("/proc/meminfo")); //get ram and swap info, some regex to split spaces and newline to store in an array

  for($i=0; $i<count($meminfo); $i++){ //get ram and swap info from the above array...
    if($meminfo[$i] === "MemTotal:")
      $ram_total=round(($meminfo[$i+1])/1024, 0); //and convert it from kb to mb, with no decimal places.
    if($meminfo[$i] === "MemFree:")
      $meminfo_memfree=round(($meminfo[$i+1])/1024, 0);
    if($meminfo[$i] === "Buffers:")
      $meminfo_buffers=round(($meminfo[$i+1])/1024, 0);
    if($meminfo[$i] === "Cached:")
      $meminfo_cached=round(($meminfo[$i+1])/1024, 0);
    if($meminfo[$i] === "SwapTotal:")
      $meminfo_swaptotal=round(($meminfo[$i+1])/1024, 0);
    if($meminfo[$i] === "SwapFree:")
      $meminfo_swapfree=round(($meminfo[$i+1])/1024, 0);
  }

  $ram_avail = $meminfo_memfree+$meminfo_buffers+$meminfo_cached; //seems the older format of the meminfo file on ubuntu 14 does not have a "MemAvailable:" value, so will add free + buffers + cached
  $ram_used=$ram_total-($meminfo_memfree+$meminfo_buffers+$meminfo_cached); //so how much ram is actually used would be the total minus free, buffers, and cached.
  $swap_used=$meminfo_swaptotal-$meminfo_swapfree; //how much swap is used is simpler to calculate.
  #########################

  ##### DISK SPACE #####
  $disk_total = round(((disk_total_space('/')/1024)/1024)/1024 ,1); //convert bytes to GB with 1 decimal place.
  $disk_free  = round(((disk_free_space ('/')/1024)/1024)/1024 ,1);
  $disk_used = round($disk_total-$disk_free, 1);
  ######################

  ##### CPU INFO #####
  $proc_stat = file('/proc/stat'); //read file into array, split by lines
  $proc_stat_cpu = preg_split('/\ +/', $proc_stat[0]); //read 1st line of file, and split into array by spaces. The first line is the aggregate of all cores
  $proc_stat_cpu['total'] = $proc_stat_cpu[1] + $proc_stat_cpu[2] + $proc_stat_cpu[3] + $proc_stat_cpu[4] + $proc_stat_cpu[5] + $proc_stat_cpu[6] + $proc_stat_cpu[7]; //100% of the cpu time
  $proc_stat_cpu['usage'] = $proc_stat_cpu[1] + $proc_stat_cpu[2] + $proc_stat_cpu[3] + $proc_stat_cpu[5] + $proc_stat_cpu[6] + $proc_stat_cpu[7]; //usage = total skipping idle

  for($i=0;$i<count($proc_stat)-3; $i++){ //for each line. -3 cause we will be adding 3 items to the array.
    $tmp = preg_split('/\ +/', $proc_stat[$i]); //split that line by spaces into array

    if($tmp[0] === "btime"){
      $proc_stat['btime'] = $tmp[1]; //time (in seconds since epoch) system has been booted. TODO: uptime
    }else if($tmp[0] === "procs_running"){
      $proc_stat['procs_running'] = $tmp[1]; //number of processes currently running
    }else if($tmp[0] === "procs_blocked"){
      $proc_stat['procs_blocked'] = $tmp[1]; //number of processes blocked (waiting for I/O to complete)
    }
  }
  ####################

  $monitor = array('ram_total' => $ram_total, 'ram_used' => $ram_used, 'ram_avail' => $ram_avail, /*'ram_free' => $meminfo_memfree, 'ram_buffers' => $meminfo_buffers, 'ram_cached' => $meminfo_cached,*/ 'swap_total' => $meminfo_swaptotal, 'swap_used' => $swap_used, 'swap_free' => $meminfo_swapfree, 'disk_total' => $disk_total, 'disk_used' => $disk_used, 'disk_free' => $disk_free, 'load_1' => $load_1, 'load_5' => $load_5, 'load_15' => $load_15, 'cores' => $cores, 'proc_stat_cpu_total' => $proc_stat_cpu['total'], 'proc_stat_cpu_usage' => $proc_stat_cpu['usage'], 'proc_stat_btime' => $proc_stat['btime'], 'proc_stat_procs_running' => $proc_stat['procs_running'], 'proc_stat_procs_blocked' => $proc_stat['procs_blocked']);

  echo json_encode($monitor); //the output
}

?>
