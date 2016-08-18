<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2016 The Cacti Group                                 |
   |                                                                         |
   | This program is free software; you can redistribute it and/or           |
   | modify it under the terms of the GNU General Public License             |
   | as published by the Free Software Foundation; either version 2          |
   | of the License, or (at your option) any later version.                  |
   |                                                                         |
   | This program is snmpagent in the hope that it will be useful,           |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
   | GNU General Public License for more details.                            |
   +-------------------------------------------------------------------------+
   | Cacti: The Complete RRDTool-based Graphing Solution                     |
   +-------------------------------------------------------------------------+
   | This code is designed, written, and maintained by the Cacti Group. See  |
   | about.php and/or the AUTHORS file for specific developer information.   |
   +-------------------------------------------------------------------------+
   | http://www.cacti.net/                                                   |
   +-------------------------------------------------------------------------+
*/

function snmpagent_cacti_stats_update($data){
	$mc = new MibCache();
	/* refresh total stats */
	$mc->object('cactiStatsTotalsDevices')->set( snmpagent_read('cactiStatsTotalsDevices') );
	$mc->object('cactiStatsTotalsDataSources')->set( snmpagent_read('cactiStatsTotalsDataSources') );
	$mc->object('cactiStatsTotalsGraphs')->set( snmpagent_read('cactiStatsTotalsGraphs') );

	/* local polling stats  - does not support distributed environments so far. */
	$mc->object('cactiStatsLocalPollerRuntime')->set($data[0]);

	$index = 1;
	$values = array(
		"cactiStatsPollerRunTime" => $data[0],
		"cactiStatsPollerConcurrentProcesses" => $data[2],
		"cactiStatsPollerThreads" => $data[3],
		"cactiStatsPollerHosts" => $data[4],
		"cactiStatsPollerHostsPerProcess" => $data[5],
		"cactiStatsPollerItems" => $data[6],
		"cactiStatsPollerRrrdsProcessed" => $data[7],
		"cactiStatsPollerUtilization" => round($data[0]/read_config_option("poller_interval", true)*100, 10)
	);
	$mc->table('cactiStatsPollerTable')->row($index)->update($values);
	$mc->object('cactiStatsLastUpdate')->set( time() );
}

function snmpagent_global_settings_update(){
	$mc = new MibCache();
	$mc->object('cactiApplVersion')->set( snmpagent_read('cactiApplVersion') );
	$mc->object('cactiApplSnmpVersion')->set( snmpagent_read('cactiApplSnmpVersion') );
	$mc->object('cactiApplRrdtoolVersion')->set( read_config_option("rrdtool_version", true) );
	$mc->object('cactiApplPollerEnabled')->set( (read_config_option("poller_enabled", true) == "on") ? 1 : 2 );
	$mc->object('cactiApplPollerType')->set( read_config_option("poller_type", true) );
	$mc->object('cactiApplPollerInterval')->set( read_config_option("poller_interval", true) );
	$mc->object('cactiApplPollerMaxProcesses')->set( read_config_option("concurrent_processes", true) );
	$mc->object('cactiApplPollerLoadBalance')->set( (read_config_option("process_leveling", true) == "on") ? 1 : 2 );
	$mc->object('cactiApplSpineMaxThreads')->set( read_config_option("max_threads", true) );
	$mc->object('cactiApplSpineScriptServers')->set( read_config_option("php_servers", true) );
	$mc->object('cactiApplSpineScriptTimeout')->set( read_config_option("script_timeout", true) );
	$mc->object('cactiApplSpineMaxOids')->set( read_config_option("max_get_size", true) );
	$mc->object('cactiApplLastUpdate')->set( time() );

	/* update boost settings */
	$mc->mib('CACTI-BOOST-MIB');
	$mc->object('boostApplRrdUpdateEnabled')->set( (read_config_option("boost_rrd_update_enable", true) == "on") ? 1 : 2 );
	$mc->object('boostApplRrdUpdateInterval')->set( read_config_option("boost_rrd_update_interval", true) );
	$mc->object('boostApplRrdUpdateMaxRecords')->set( read_config_option("boost_rrd_update_max_records", true) );
	$mc->object('boostApplRrdUpdateMaxRecordsPerSelect')->set( read_config_option("boost_rrd_update_max_records_per_select", true) );
	$mc->object('boostApplRrdUpdateMaxStringLength')->set( read_config_option("boost_rrd_update_string_length", true) );
	$mc->object('boostApplRrdUpdatePollerMemLimit')->set( read_config_option("boost_poller_mem_limit", true) );
	$mc->object('boostApplRrdUpdateMaxRunTime')->set( read_config_option("boost_rrd_update_max_runtime", true) );
	$mc->object('boostApplRrdUpdateRedirect')->set( (read_config_option("boost_redirect", true) == "on") ? 1 : 2 );
	$mc->object('boostApplImageCacheEnabled')->set( (read_config_option("boost_png_cache_enable", true) == "on") ? 1 : 2 );
	$mc->object('boostApplLoggingEnabled')->set( (read_config_option("path_boost_log", true) == true) ? 1 : 2 );
	$mc->object('boostApplLastUpdate')->set( time() );
}

function snmpagent_api_device_new($device){
	$mc = new MibCache();
	/* add device to cactiApplDeviceTable and cactiStatsDeviceTable*/
	$device_data = db_fetch_row("SELECT * FROM `host` WHERE id = " . $device["id"]);

	$appl_values = array(
		"cactiApplDeviceIndex" => $device_data["id"],
		"cactiApplDeviceDescription" => $device_data["description"],
		"cactiApplDeviceHostname" => $device_data["hostname"],
		"cactiApplDeviceStatus" => $device_data["status"],
		"cactiApplDeviceEventCount" => $device_data["status_event_count"],
		"cactiApplDeviceFailDate" => $device_data["status_fail_date"],
		"cactiApplDeviceRecoveryDate" => $device_data["status_rec_date"],
		"cactiApplDeviceLastError" => $device_data["status_last_error"],
	);

	$stats_values = array(
		"cactiStatsDeviceIndex" => $device_data["id"],
		"cactiStatsDeviceHostname" => $device_data["hostname"],
		"cactiStatsDeviceMinTime" => $device_data["min_time"],
		"cactiStatsDeviceMaxTime" => $device_data["max_time"],
		"cactiStatsDeviceCurTime" => $device_data["cur_time"],
		"cactiStatsDeviceAvgTime" => $device_data["avg_time"],
		"cactiStatsDeviceTotalPolls" => $device_data["total_polls"],
		"cactiStatsDeviceFailedPolls" => $device_data["failed_polls"],
		"cactiStatsDeviceAvailability" => $device_data["availability"]
	);

	$mc->table('cactiApplDeviceTable')->row($device["id"])->replace($appl_values);
	$mc->object('cactiApplLastUpdate')->set( time() );

	$mc->table('cactiStatsDeviceTable')->row($device["id"])->replace($stats_values);
	$mc->object('cactiStatsTotalsDevices')->set( snmpagent_read('cactiStatsTotalsDevices') );
	$mc->object('cactiStatsLastUpdate')->set( time() );
}

function snmpagent_data_source_action_bottom($data){
	$mc = new MibCache();
	$action = $data[0];
	if($action == "1") {
		/* delete data sources */
		$mc->object('cactiStatsTotalsDataSources')->set( snmpagent_read('cactiStatsTotalsDataSources') );
		$mc->object('cactiStatsTotalsGraphs')->set( snmpagent_read('cactiStatsTotalsGraphs') );
		$mc->object('cactiStatsLastUpdate')->set( time() );
	}elseif($action == "4") {
		/* duplicate data sources */
		$mc->object('cactiStatsTotalsDataSources')->set( snmpagent_read('cactiStatsTotalsDataSources') );
		$mc->object('cactiStatsLastUpdate')->set( time() );
	}
}

function snmpagent_graphs_action_bottom($data){
	$mc = new MibCache();
	$action = $data[0];
	if($action == "1") {
		/* delete graphs */
		$mc->object('cactiStatsTotalsDataSources')->set( snmpagent_read('cactiStatsTotalsDataSources') );
		$mc->object('cactiStatsTotalsGraphs')->set( snmpagent_read('cactiStatsTotalsGraphs') );
		$mc->object('cactiStatsLastUpdate')->set( time() );
	}elseif($action == "3") {
		/* duplicate graphs */
		$mc->object('cactiStatsTotalsGraphs')->set( snmpagent_read('cactiStatsTotalsGraphs') );
		$mc->object('cactiStatsLastUpdate')->set( time() );
	}
}

function snmpagent_device_action_bottom($data){
	$mc = new MibCache();
	$action = $data[0];
	$selected_items = sanitize_unserialize_selected_items($data[1]);

	if ($selected_items != false) {
		switch($action){
			case "1":
				/* delete devices */
				foreach($selected_items as $device_id) {
					$mc->table('cactiApplDeviceTable')->row($device_id)->delete();
					$mc->table('cactiStatsDeviceTable')->row($device_id)->delete();
				}

				/* update total statistics */
				$mc->object('cactiStatsTotalsDevices')->set( snmpagent_read('cactiStatsTotalsDevices') );
				$mc->object('cactiStatsTotalsDataSources')->set( snmpagent_read('cactiStatsTotalsDataSources') );
				$mc->object('cactiStatsTotalsGraphs')->set( snmpagent_read('cactiStatsTotalsGraphs') );
				$mc->object('cactiStatsLastUpdate')->set( time() );
				break;
			case "2":
				/* enable devices */
				foreach($selected_items as $device_id) {
					$device_status = db_fetch_cell("SELECT status FROM host WHERE id = " . $device_id);
					$mc->table('cactiApplDeviceTable')->row($device_id)->update(array('cactiApplDeviceStatus' => $device_status));
				}
				$mc->object('cactiApplLastUpdate')->set( time() );
				break;
			case "3":
				/* disable devices */
				foreach($selected_items as $device_id) {
					$device_status = db_fetch_cell("SELECT status FROM host WHERE id = " . $device_id);
					$mc->table('cactiApplDeviceTable')->row($device_id)->update(array('cactiApplDeviceStatus' => 4));
				}
				$mc->object('cactiApplLastUpdate')->set( time() );
				break;
			case "5":
				/* clear device statisitics */
				$values = array(
					'cactiStatsDeviceMinTime' => '9.99999',
					'cactiStatsDeviceMaxTime' => '0',
					'cactiStatsdeviceCurTime' => '0',
					'cactiStatsDeviceAvgTime' => '0',
					'cactiStatsDeviceTotalPolls' => '0',
					'cactiStatsDeviceFailedPolls' => '0',
					'cactiStatsDeviceAvailability' => '100'
				);
				foreach($selected_items as $device_id) {
					$mc->table('cactiStatsDeviceTable')->row($device_id)->update($values);
				}
				$mc->object('cactiStatsLastUpdate')->set( time() );
				break;

			default:
				/* nothing to do */
			;
		} //switch
	}
}

function snmpagent_poller_exiting($poller_index = 1){
	$mc = new MibCache();
	$poller = $mc->table('cactiApplPollerTable')->row($poller_index)->select();
	$varbinds = array(
		'cactiApplPollerIndex' => $poller_index,
		'cactiApplPollerHostname' => $poller['cactiApplPollerHostname'],
		'cactiApplPollerIpAddress' => $poller['cactiApplPollerIpAddress']
	);
	snmpagent_notification('cactiNotifyPollerRuntimeExceeding', 'CACTI-MIB', $varbinds, SNMPAGENT_EVENT_SEVERITY_HIGH);
}

function snmpagent_poller_bottom() {
	global $config;

	if (api_plugin_is_enabled('maint')) {
		include_once($config["base_path"] . '/plugins/maint/functions.php');
	}

	$device_in_maintenance = false;
	$mc = new MibCache();
	/* START: update total device stats table */
	/***** deprecated ******/
	$devicestatus_indices = array( 0=> 0, 1=> 1, 2=> 2, 3=> 3, 4=> 4);
	$current_states = db_fetch_assoc("SELECT status, COUNT(*) as cnt FROM `host` GROUP BY status");
	if($current_states && sizeof($current_states)>0) {
		foreach($current_states as $current_state) {
			$index = $devicestatus_indices[$current_state["status"]];
			$values = array(
				"cactiStatsTotalsDeviceStatusIndex" => $current_state["status"],
				"cactiStatsTotalsDeviceStatusCounter" => $current_state["cnt"]
			);
			$mc->table('cactiStatsTotalsDeviceStatusTable')->row($index)->replace($values);
			unset($devicestatus_indices[$current_state["status"]]);
		}
	}
	if(sizeof($devicestatus_indices)>0) {
		foreach($devicestatus_indices as $status => $index) {
			$values = array(
				"cactiStatsTotalsDeviceStatusIndex" => $status,
				"cactiStatsTotalsDeviceStatusCounter" => 0
			);
			$mc->table('cactiStatsTotalsDeviceStatusTable')->row($index)->replace($values);
		}
	}
	/************************/
	$mc->object('cactiStatsTotalsDeviceStatusUnknown')->set( snmpagent_read('cactiStatsTotalsDeviceStatusUnknown') );
	$mc->object('cactiStatsTotalsDeviceStatusDown')->set( snmpagent_read('cactiStatsTotalsDeviceStatusDown') );
	$mc->object('cactiStatsTotalsDeviceStatusRecovering')->set( snmpagent_read('cactiStatsTotalsDeviceStatusRecovering') );
	$mc->object('cactiStatsTotalsDeviceStatusUp')->set( snmpagent_read('cactiStatsTotalsDeviceStatusUp') );
	$mc->object('cactiStatsTotalsDeviceStatusDisabled')->set( snmpagent_read('cactiStatsTotalsDeviceStatusDisabled') );
	/* END: update total device stats table */

	/* update state and statistics of all devices */
	$mc_dstatus = array();
	$mc_devices = $mc->table('cactiApplDeviceTable')->select(array('cactiApplDeviceIndex', 'cactiApplDeviceStatus'));
	if($mc_devices && sizeof($mc_devices)>0) {
		foreach($mc_devices as $mc_device) {
			$mc_dstatus[$mc_device['cactiApplDeviceIndex']] = $mc_device['cactiApplDeviceStatus'];
		}
	}
	$mc_dfailed = array();
	$mc_device_stats = $mc->table('cactiStatsDeviceTable')->select(array('cactiStatsDeviceIndex','cactiStatsDeviceFailedPolls'));
	if($mc_device_stats && sizeof($mc_device_stats)>0) {
		foreach($mc_device_stats as $mc_device_stat) {
			$mc_dfailed[$mc_device_stat['cactiStatsDeviceIndex']] = $mc_device_stat['cactiStatsDeviceFailedPolls'];
		}
	}

	$devices = db_fetch_assoc("SELECT id, description, hostname, status, disabled, status_event_count, status_fail_date, status_rec_date, status_last_error, min_time, max_time, cur_time, avg_time, total_polls, failed_polls, availability FROM host ORDER BY id ASC");
	if($devices && sizeof($devices)>0) {
		foreach($devices as $device) {
			if(function_exists('plugin_maint_check_cacti_host')) {
				$device_in_maintenance = plugin_maint_check_cacti_host($index);
			}
			if(!$device_in_maintenance) {
				$varbinds = array(
					'cactiApplDeviceIndex' => $device["id"],
					'cactiApplDeviceDescription' => $device["description"],
					'cactiApplDeviceHostname' => $device["hostname"],
					'cactiApplDeviceLastError' => $device["status_last_error"]
				);
				if($device["failed_polls"] > $mc_dfailed[$device["id"]]) {
					snmpagent_notification('cactiNotifyDeviceFailedPoll', 'CACTI-MIB', $varbinds);
				}
				if($mc_dstatus[$device["id"]] == HOST_UP && $device["status"] == HOST_DOWN ) {
					snmpagent_notification('cactiNotifyDeviceDown', 'CACTI-MIB', $varbinds, SNMPAGENT_EVENT_SEVERITY_HIGH);
				}elseif ($mc_dstatus[$device["id"]] == HOST_DOWN && $device["status"] == HOST_RECOVERING ){
					snmpagent_notification('cactiNotifyDeviceRecovering', 'CACTI-MIB', $varbinds);
				}
			}

			$values = array(
				"cactiApplDeviceStatus" => ($device["disabled"] == 'on') ? 4 : $device["status"],
				"cactiApplDeviceEventCount" => $device["status_event_count"],
				"cactiApplDeviceFailDate" => $device["status_fail_date"],
				"cactiApplDeviceRecoveryDate" => $device["status_rec_date"],
				"cactiApplDeviceLastError" => $device["status_last_error"]
			);
			$mc->table('cactiApplDeviceTable')->row($device["id"])->update($values);
			$values = array(
				"cactiStatsDeviceMinTime" => $device["min_time"],
				"cactiStatsDeviceMaxTime" => $device["max_time"],
				"cactiStatsDeviceCurTime" => $device["cur_time"],
				"cactiStatsDeviceAvgTime" => $device["avg_time"],
				"cactiStatsDeviceTotalPolls" => $device["total_polls"],
				"cactiStatsDeviceFailedPolls" => $device["failed_polls"],
				"cactiStatsDeviceAvailability" => $device["availability"]
			);
			$mc->table('cactiStatsDeviceTable')->row($device["id"])->update($values);
		}
	}

	/* get a list of all plugins available on that system */
	$pluginslist = snmpagent_get_pluginslist();
	/* truncate plugin mib table */
	$mc->table('cactiApplPluginTable')->truncate();
	/* refill plugin mib table */
	if($pluginslist && sizeof($pluginslist)>0) {
		$i = 1;
		foreach($pluginslist as $plugin) {
			$values = array(
				"cactiApplPluginIndex" => $i,
				"cactiApplPluginType" => 2,
				"cactiApplPluginName" => $plugin["directory"],
				"cactiApplPluginStatus" => $plugin["status"],
				"cactiApplPluginVersion" => $plugin["version"]
			);
			$mc->table('cactiApplPluginTable')->row($i)->insert($values);
			$i++;
		}
	}
	$mc->object('cactiApplLastUpdate')->set( time() );

	$recache_stats = db_fetch_cell("SELECT value FROM settings WHERE name = 'stats_recache'");
	if($recache_stats) {
		list($time, $hosts) = explode(" ", $recache_stats);
		$time = str_replace("RecacheTime:", "", $time);
		$hosts = str_replace("HostsRecached:", "", $hosts);
	}
	$mc->object('cactiStatsRecacheTime')->set($time);
	$mc->object('cactiStatsRecachedHosts')->set($hosts);
	$mc->object('cactiStatsLastUpdate')->set( time() );

	/* clean up the notification log */
	$snmp_notification_managers = db_fetch_assoc("SELECT id, max_log_size FROM snmpagent_managers");
	if($snmp_notification_managers && sizeof($snmp_notification_managers)>0) {
		foreach($snmp_notification_managers as $snmp_notification_manager) {
			db_execute("DELETE FROM snmpagent_notifications_log WHERE manager_id = " . $snmp_notification_manager["id"] . " AND `time` <= " . (time()-86400*$snmp_notification_manager["max_log_size"]) );
		}
	}
}

function snmpagent_get_pluginslist(){
	global $config, $plugins, $plugins_integrated;
	/* update the list of known plugins only once per polling cycle. In all other cases we would
	   have to create too many new hooks to update that MIB table just in time.
	   We have to do the same like function plugins_load_temp_table(), which will not be available
	   during the execution of that function. */

	$pluginslist = array();
	$registered_plugins = db_fetch_assoc('SELECT * FROM plugin_config ORDER BY name');
	foreach ($registered_plugins as $t) {
		$pluginslist[$t['directory']] = $t;
	}

	$path = $config['base_path'] . '/plugins/';
	$dh = opendir($path);
	if ($dh !== false) {
		while (($file = readdir($dh)) !== false) {
			if ((is_dir("$path/$file")) && !in_array($file, $plugins_integrated) && (file_exists("$path/$file/setup.php")) && (!array_key_exists($file, $pluginslist))) {
				include_once("$path/$file/setup.php");
				if (!function_exists('plugin_' . $file . '_install') && function_exists($file . '_version')) {
					$function = $file . '_version';
					$cinfo = $function();
					if (!isset($cinfo['author']))   $cinfo['author']   = 'Unknown';
					if (!isset($cinfo['homepage'])) $cinfo['homepage'] = 'Not Stated';
					if (isset($cinfo['webpage']))   $cinfo['homepage'] = $cinfo['webpage'];
					if (!isset($cinfo['longname'])) $cinfo['longname'] = ucfirst($file);
					$cinfo['status'] = -2; /* old PIA -- disabled */
					if (in_array($file, $plugins)) {
						$cinfo['status'] = -1; /* old PIA -- enabled */
					}
					$cinfo['directory'] = $file;
					$pluginslist[$file] = $cinfo;

				} elseif (function_exists('plugin_' . $file . '_install') && function_exists('plugin_' . $file . '_version')) {
					$function = 'plugin_' . $file . '_version';
					$cinfo = $function();
					$cinfo['status'] = 0;
					if (!isset($cinfo['author']))   $cinfo['author']   = 'Unknown';
					if (!isset($cinfo['homepage'])) $cinfo['homepage'] = 'Not Stated';
					if (isset($cinfo['webpage']))   $cinfo['homepage'] = $cinfo['webpage'];
					if (!isset($cinfo['longname'])) $cinfo['homepage'] = ucfirst($file);
					$cinfo['directory'] = $file;
					$pluginslist[$file] = $cinfo;
				}
			}
		}
		closedir($dh);
	}
	return $pluginslist;
}

/**
 * snmpagent_cache_setup()
 * Generates a SNMP caching tables reflecting all objects of the Cacti MIB
 * @return
 */
function snmpagent_cache_install(){
	global $config;

	/* drop everything */
	db_execute("TRUNCATE `snmpagent_cache`");
	db_execute("TRUNCATE `snmpagent_mibs`;");
	db_execute("TRUNCATE `snmpagent_cache_notifications`;");
	db_execute("TRUNCATE `snmpagent_cache_textual_conventions`;");

	$mc = new MibCache();
	$mc->install($config["base_path"] . '/mibs/CACTI-MIB');
	$mc->install($config["base_path"] . '/mibs/CACTI-SNMPAGENT-MIB');
	$mc->install($config["base_path"] . '/mibs/CACTI-BOOST-MIB');
	snmpagent_cache_init();

	/* call install routine of plugins supporting the SNMPagent */
	do_hook('snmpagent_cache_install');
}

function snmpagent_cache_rebuilt(){
	snmpagent_cache_install();
}

function snmpagent_cache_init(){
	/* fill up the cache with a minimum of data data and ignore all values that
	   *  will be updated automatically at the bottom of the next poller run
	*/
	$mc = new MibCache();
	/* update global settings */
	snmpagent_global_settings_update();

	/* add pollers of a distributed system (future) */
	$pollers = db_fetch_assoc("SELECT id FROM poller ORDER BY id ASC");
	if($pollers && sizeof($pollers)>0) {
		foreach($pollers as $poller){
			$poller_data = db_fetch_row("SELECT * FROM poller WHERE id = " . $poller["id"]);
		}
	}else {
		/* this is NOT a distributed system, but it should have at least one local poller. */
		$poller_lastrun = read_config_option("poller_lastrun", true);
		$values = array(
			"cactiApplPollerIndex" => 1,
			"cactiApplPollerHostname" => "localhost",
			"cactiApplPollerIpAddress" => "127.0.0.1",
			"cactiApplPollerLastUpdate" => $poller_lastrun
		);
		$mc->table('cactiApplPollerTable')->row(1)->insert($values);

		$values = array(
			"cactiStatsPollerIndex" => 1,
			"cactiStatsPollerHostname" => "localhost",
			"cactiStatsPollerMethod" => read_config_option("poller_type", true)
		);
		$mc->table('cactiStatsPollerTable')->row(1)->insert($values);
	}

	/* add all devices as devicetable entries to the snmp cache */
	$devices = db_fetch_assoc("SELECT id, description, hostname, disabled, status_event_count, status_fail_date, status_rec_date, status_last_error, min_time, max_time, cur_time, avg_time, total_polls, failed_polls, availability FROM host ORDER BY id ASC");
	if($devices && sizeof($devices)>0) {
		foreach($devices as $device) {
			$device = db_fetch_row("SELECT * FROM host WHERE id = " . $device["id"]);
			/* add device to cactiApplDeviceTable */
			$values = array(
				"cactiApplDeviceIndex" => $device["id"],
				"cactiApplDeviceDescription" => $device["description"],
				"cactiApplDeviceHostname" => $device["hostname"],
				"cactiApplDeviceStatus" => ($device["disabled"] == 'on') ? 4 : $device["status"],
				"cactiApplDeviceEventCount" => $device["status_event_count"],
				"cactiApplDeviceFailDate" => $device["status_fail_date"],
				"cactiApplDeviceRecoveryDate" => $device["status_rec_date"],
				"cactiApplDeviceLastError" => $device["status_last_error"],
			);
			$mc->table('cactiApplDeviceTable')->row($device["id"])->insert($values);

			/* add device to cactiStatsDeviceTable */
			$values = array(
				"cactiStatsDeviceIndex" => $device["id"],
				"cactiStatsDeviceHostname" => $device["hostname"],
				"cactiStatsDeviceMinTime" => $device["min_time"],
				"cactiStatsDeviceMaxTime" => $device["max_time"],
				"cactiStatsDeviceCurTime" => $device["cur_time"],
				"cactiStatsDeviceAvgTime" => $device["avg_time"],
				"cactiStatsDeviceTotalPolls" => $device["total_polls"],
				"cactiStatsDeviceFailedPolls" => $device["failed_polls"],
				"cactiStatsDeviceAvailability" => $device["availability"]
			);
			$mc->table('cactiStatsDeviceTable')->row($device["id"])->insert($values);
		}
	}
}

function snmpagent_read($object){
	switch($object) {
		case "cactiApplVersion":
			$value = db_fetch_cell("SELECT `cacti` FROM `version`");
			break;
		case "cactiApplSnmpVersion":
			$snmp_version = read_config_option("snmp_version", true);
			$value = $snmp_version;
			if(function_exists("snmpget")) {
				$value = 3;
			}
			break;
		case "cactiStatsTotalsDevices":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host");
			break;
		case "cactiStatsTotalsDataSources":
			$value = db_fetch_cell("SELECT COUNT(*) FROM data_local");
			break;
		case "cactiStatsTotalsGraphs":
			$value = db_fetch_cell("SELECT COUNT(*) FROM graph_local");
			break;
		case "cactiStatsTotalsDeviceStatusUnknown":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host WHERE status = 0");
			break;
		case "cactiStatsTotalsDeviceStatusDown":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host WHERE status = 1");
			break;
		case "cactiStatsTotalsDeviceStatusRecovering":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host WHERE status = 2");
			break;
		case "cactiStatsTotalsDeviceStatusUp":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host WHERE status = 3");
			break;
		case "cactiStatsTotalsDeviceStatusDisabled":
			$value = db_fetch_cell("SELECT COUNT(*) FROM host WHERE status = 4");
			break;
		default:
			$value = false;
	}
	return $value;
}

function snmpagent_notification( $notification, $mib, $varbinds, $severity = SNMPAGENT_EVENT_SEVERITY_MEDIUM){
	global $config;

	if(isset($config["snmpagent"]["notifications"]["ignore"][$notification])) {
		return false;
	}

	$path_snmptrap = read_config_option("snmpagent_path_snmptrap");

	if( !in_array( $severity, array( SNMPAGENT_EVENT_SEVERITY_LOW, SNMPAGENT_EVENT_SEVERITY_MEDIUM, SNMPAGENT_EVENT_SEVERITY_HIGH, SNMPAGENT_EVENT_SEVERITY_CRITICAL ) ) ) {
		if(read_config_option('log_verbosity')>POLLER_VERBOSITY_NONE) {
			cacti_log('ERROR: Unknown event severity: "' . $severity . '" for ' . $notification . ' (' . $mib . ')', false, 'SNMPAGENT');
		}
		return false;
	}

	$enterprise_oid = db_fetch_cell("SELECT oid from snmpagent_cache where `name` = '" . $notification . "' AND `mib` = '" . $mib ."'");
	if(!$enterprise_oid) {
		/* system does not know this event */
		if(read_config_option('log_verbosity')>POLLER_VERBOSITY_NONE) {
			cacti_log('ERROR: Unknown event: ' . $notification . ' (' . $mib . ')', false, 'SNMPAGENT');
		}
		return false;
	}else {
		$branches = explode(".", $enterprise_oid);
		$specific_trap_number = array_pop($branches);
	}

	/* generate a list of SNMP notification receivers listening for this notification */
	$sql = "SELECT snmpagent_managers.*
				FROM snmpagent_managers_notifications
					INNER JOIN snmpagent_managers
					ON (
						snmpagent_managers.id = snmpagent_managers_notifications.manager_id
					)
				WHERE snmpagent_managers.disabled = 0 AND snmpagent_managers_notifications.notification = '$notification' AND snmpagent_managers_notifications.mib = '$mib'";

	$notification_managers = db_fetch_assoc($sql);

	if(!$notification_managers) {
		/* To bad! Nobody wants to hear our message. :( */
		if(in_array($severity, array(SNMPAGENT_EVENT_SEVERITY_HIGH, SNMPAGENT_EVENT_SEVERITY_CRITICAL))) {
			if(read_config_option('log_verbosity')>POLLER_VERBOSITY_NONE) {
				cacti_log('WARNING: No notification receivers configured for event: ' . $notification . ' (' . $mib . ')', false, 'SNMPAGENT');
			}
		}else {
			/* keep notifications of a lower/medium severity in mind to make a quicker decision next time */
			$config["snmpagent"]["notifications"]["ignore"][$notification] = 1;
		}
		return false;
	}

	$registered_var_binds = array();
	/* get a list of registered var binds */
	$sql = "SELECT
				snmpagent_cache_notifications.attribute,
				snmpagent_cache.oid,
				snmpagent_cache.type,
				snmpagent_cache_textual_conventions.type as tcType
			FROM snmpagent_cache_notifications
				LEFT JOIN snmpagent_cache
				ON (
					snmpagent_cache.mib = snmpagent_cache_notifications.mib
					AND
					snmpagent_cache.name = snmpagent_cache_notifications.attribute
				)
				LEFT JOIN snmpagent_cache_textual_conventions
				ON (
					snmpagent_cache.mib = snmpagent_cache_textual_conventions.mib
					AND
					snmpagent_cache.type = snmpagent_cache_textual_conventions.name
				)
			WHERE snmpagent_cache_notifications.name = '$notification' AND snmpagent_cache_notifications.mib = '$mib'
			ORDER BY snmpagent_cache_notifications.sequence_id";

	$reg_var_binds = db_fetch_assoc($sql);

	if($reg_var_binds && sizeof($reg_var_binds)>0) {
		foreach($reg_var_binds as $reg_var_bind) {
			$registered_var_binds[$reg_var_bind["attribute"]] = array(
				"oid" => $reg_var_bind["oid"],
				"type" => ($reg_var_bind["tcType"]) ? $reg_var_bind["tcType"] : $reg_var_bind["type"]
			);
		}
	}

	$difference = array_diff( array_keys($registered_var_binds), array_keys($varbinds));

	if(sizeof($difference) == 0) {
		/* order the managers by message type to send out all notifications immmediately. Informs
		   will take more processing time.
		*/
		$sql = "SELECT snmpagent_managers.* FROM snmpagent_managers_notifications
					INNER JOIN snmpagent_managers
					ON (
						snmpagent_managers.id = snmpagent_managers_notifications.manager_id
					)
				WHERE snmpagent_managers_notifications.notification = '$notification' AND snmpagent_managers_notifications.mib = '$mib'
				ORDER BY snmpagent_managers.snmp_message_type";

		$notification_managers = db_fetch_assoc($sql);

		if($notification_managers && sizeof($notification_managers)>0) {

			include_once($config["library_path"] . "/poller.php");

			/*
			TYPE: one of i, u, t, a, o, s, x, d, b
				i: INTEGER, u: unsigned INTEGER, t: TIMETICKS, a: IPADDRESS
				o: OBJID, s: STRING, x: HEX STRING, d: DECIMAL STRING, b: BITS
				U: unsigned int64, I: signed int64, F: float, D: double
			*/
			$smi2netsnmp_datatypes = array(
				"integer" 			=> "i",
				"integer32"			=> "i",
				"unsigned32" 		=> "u",
				"gauge" 			=> "i",
				"gauge32" 			=> "i",
				"counter" 			=> "i",
				"counter32" 		=> "i",
				"counter64" 		=> "I",
				"timeticks" 		=> "t",
				"octect string" 	=> "s",
				"opaque"			=> "s",
				"object identifier" => "o",
				"ipaddress" 		=> "a",
				"networkaddress" 	=> "IpAddress",
				"bits" 				=> "b",
				"displaystring" 	=> "s",
				"physaddress" 		=> "s",
				"macaddress" 		=> "s",
				"truthvalue" 		=> "i",
				"testandincr" 		=> "i",
				"autonomoustype" 	=> "o",
				"variablepointer" 	=> "o",
				"rowpointer" 		=> "o",
				"rowstatus" 		=> "i",
				"timestamp" 		=> "t",
				"timeinterval" 		=> "i",
				"dateandtime" 		=> "s",
				"storagetype" 		=> "i",
				"tdomain" 			=> "o",
				"taddress" 			=> "s"
			);

			$log_notification_varbinds = "";
			$snmp_notification_varbinds = "";

			foreach($notification_managers as $notification_manager) {
				if(!$snmp_notification_varbinds) {
					foreach($registered_var_binds as $name => $attributes ) {
						$snmp_notification_varbinds .= " " . $attributes["oid"] . " " . $smi2netsnmp_datatypes[strtolower($attributes["type"])] . " \"" . str_replace('"', "'", $varbinds[$name]) . "\"";
						$log_notification_varbinds .= $name . ":\"" . str_replace('"', "'", $varbinds[$name]) . "\" ";
					}
				}

				if($notification_manager["snmp_version"] == 1 ) {
					$args = " -v 1 -c " . $notification_manager["snmp_community"] . " " . $notification_manager["hostname"] . ":" . $notification_manager["snmp_port"] . " " . $enterprise_oid . " \"\" 6 " . $specific_trap_number . " \"\"" . $snmp_notification_varbinds;
				}else if($notification_manager["snmp_version"] == 2 ) {
					$args = " -v 2c -c " . $notification_manager["snmp_community"] . ( ($notification_manager["snmp_message_type"] == 2 )? " -Ci " : "" )  . " " . $notification_manager["hostname"] . ":" . $notification_manager["snmp_port"] . " \"\" " . $enterprise_oid . $snmp_notification_varbinds;
				}else if($notification_manager["snmp_version"] == 3 ) {
					$args = " -v 3 -e " . $notification_manager["snmp_engine_id"] . (($notification_manager["snmp_message_type"] == 2 )? " -Ci " : "" ) .  " -u " . $notification_manager["snmp_username"];

					if( $notification_manager["snmp_auth_password"] && $notification_manager["snmp_priv_password"]) {
						$snmp_security_level = "authPriv";
					}elseif ( $notification_manager["snmp_auth_password"] && !$notification_manager["snmp_priv_password"]) {
						$snmp_security_level = "authNoPriv";
					}else {
						$snmp_security_level = "noAuthNoPriv";
					}
					$args .= " -l " . $snmp_security_level . (($snmp_security_level != "noAuthNoPriv") ? " -a " . $notification_manager["snmp_auth_protocol"] . " -A " . $notification_manager["snmp_auth_password"] : "" ) . (($snmp_security_level == "authPriv")? " -x " . $notification_manager["snmp_priv_protocol"] . " -X " . $notification_manager["snmp_priv_password"] : "")  . " " . $notification_manager["hostname"] . ":" . $notification_manager["snmp_port"] . " \"\" " . $enterprise_oid . $snmp_notification_varbinds;
				}

				/* execute net-snmp to generate this notification in the background */
				exec_background( escapeshellcmd($path_snmptrap), escapeshellcmd($args));

				/* insert a new entry into the notification log for that SNMP receiver */
				$save = array();
				$save["id"]				= 0;
				$save["time"]			= time();
				$save["severity"]		= $severity;
				$save["manager_id"]		= $notification_manager["id"];
				$save["notification"]	= $notification;
				$save["mib"]			= $mib;
				$save["varbinds"]		= mysqli_real_escape_string( substr($log_notification_varbinds, 0, 5000) );
				sql_save( $save, 'snmpagent_notifications_log');

				/* log the net-snmp command for Cacti admins if they wish for */
				if(read_config_option('log_verbosity')>POLLER_VERBOSITY_MEDIUM) {
					cacti_log("NOTE: $path_snmptrap " . str_replace(array($notification_manager["snmp_auth_password"], $notification_manager["snmp_priv_password"]), '********', $args), false, 'SNMPAGENT');
				}
			}
		}
	}else {
		/* mismatching number of var binds */
		if(read_config_option('log_verbosity')>POLLER_VERBOSITY_NONE) {
			cacti_log('ERROR: Incomplete number of varbinds given for event: ' . $notification . ' (' . $mib . ')', false, 'SNMPAGENT');
		}
		return false;
	}
}

