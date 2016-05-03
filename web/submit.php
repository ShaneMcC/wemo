<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
		$location = $_SERVER['PHP_AUTH_USER'];
		$key = $_SERVER['PHP_AUTH_PW'];

		if (!isset($probes[$location]) || $probes[$location] != $key) {
			unset($_SERVER['PHP_AUTH_USER']);
			unset($_SERVER['PHP_AUTH_PW']);
		}
	}

	if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
		header('WWW-Authenticate: Basic realm="WEMO Probe Data"');
		header('HTTP/1.0 401 Unauthorized');

		die(json_encode(array('error' => 'Unauthorized')));
	}

	if (!file_exists($rrdtool)) { die(json_encode(array('error' => 'Internal Error'))); }

	$postdata = file_get_contents("php://input");
	$data = @json_decode($postdata, true);
	if ($data === null) { die(json_encode(array('error' => 'Invalid Data'))); }
	$data['location'] = preg_replace('#[^a-z0-9-_ ]#', '', strtolower($location));

	foreach ($data['devices'] as $dev) {
		$dir = $rrdDir . '/' . $data['location'] . '/' . $dev['serial'];
		if (!file_exists($dir)) { mkdir($dir, 0755, true); }
		if (!file_exists($dir)) { die(json_encode(array('error' => 'Internal Error'))); }

		$meta = $dev;
		unset($meta['data']);
		@file_put_contents($dir . '/meta.js', json_encode($meta));

		// =====================================================================
		// Store instantPower
		// =====================================================================
		// Based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
		$instantPower = $dir . '/instantPower.rrd';
		if (!file_exists($instantPower)) {
			$rrdData = array();
			$rrdData[] = 'create "' . $instantPower . '"';
			$rrdData[] = '--start now';
			$rrdData[] = '--step 60';
			$rrdData[] = 'DS:instantPower:GAUGE:120:U:U';
			$rrdData[] = 'RRA:AVERAGE:0.5:1:1440';
			$rrdData[] = 'RRA:AVERAGE:0.5:10:1008';
			$rrdData[] = 'RRA:AVERAGE:0.5:30:1488';
			$rrdData[] = 'RRA:AVERAGE:0.5:120:1488';
			$rrdData[] = 'RRA:AVERAGE:0.5:360:1488';
			$rrdData[] = 'RRA:AVERAGE:0.5:1440:36500';

			execRRDTool($rrdData);
		}
		if (!file_exists($instantPower)) { die(json_encode(array('error' => 'Internal Error'))); }

		$rrdData = array();
		$rrdData[] = 'update "' . $instantPower . '"';
		$rrdData[] = '--skip-past-updates';
		$rrdData[] = '--template instantPower';
		$rrdData[] = $data['time'] . ':' . $dev['data']['instantPower'];
		execRRDTool($rrdData);
		// =====================================================================
	}

	die(json_encode(array('success' => 'ok')));
