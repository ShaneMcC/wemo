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


	$DATATYPES = array();
	$DATATYPES['instantPower'] = ['instantPower', 'GAUGE'];
	$DATATYPES['REAL_POWER'] = ['REAL_POWER', 'GAUGE'];

	$postdata = file_get_contents("php://input");
	$data = @json_decode($postdata, true);
	if ($data === null) { die(json_encode(array('error' => 'Invalid Data'))); }
	$data['location'] = preg_replace('#[^a-z0-9-_ ]#', '', strtolower($location));

	foreach ($data['devices'] as $dev) {
		$dev['serial'] = preg_replace('#[^A-Z0-9-_ ]#', '', strtoupper($dev['serial']));
		$dir = $rrdDir . '/' . $data['location'] . '/' . $dev['serial'];
		if (!file_exists($dir)) { mkdir($dir, 0755, true); }
		if (!file_exists($dir)) { die(json_encode(array('error' => 'Internal Error'))); }

		$meta = $dev;
		unset($meta['data']);
		@file_put_contents($dir . '/meta.js', json_encode($meta));

		foreach ($dev['data'] as $dataPoint => $dataValue) {
			if (!isset($DATATYPES[$dataPoint])) { continue; }

			list($dsname, $dstype) = $DATATYPES[$dataPoint];
			$storeValue = $dataValue;

			$rrdDataFile = $dir . '/'.$dsname.'.rrd';
			if (!file_exists($rrdDataFile)) { createRRD($rrdDataFile, $dsname, $dstype, $data['time']); }
			if (!file_exists($rrdDataFile)) { die(json_encode(array('error' => 'Internal Error'))); }

			updateRRD($rrdDataFile, $dsname, $data['time'], $storeValue);
		}
	}

	function createRRD($filename, $dsname, $dstype, $startTime) {
		// Based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
		$rrdData = array();
		$rrdData[] = 'create "' . $filename . '"';
		$rrdData[] = '--start ' . $startTime;
		$rrdData[] = '--step 60';
		$rrdData[] = 'DS:' . $dsname . ':' . $dstype . ':120:U:U';
		$rrdData[] = 'RRA:AVERAGE:0.5:1:1440';
		$rrdData[] = 'RRA:AVERAGE:0.5:10:1008';
		$rrdData[] = 'RRA:AVERAGE:0.5:30:1488';
		$rrdData[] = 'RRA:AVERAGE:0.5:120:1488';
		$rrdData[] = 'RRA:AVERAGE:0.5:360:1488';
		$rrdData[] = 'RRA:AVERAGE:0.5:1440:36500';

		execRRDTool($rrdData);
	}

	function updateRRD($filename, $dsname, $time, $value) {
		$rrdData = array();
		$rrdData[] = 'update "' . $filename . '"';
		// $rrdData[] = '--skip-past-updates';
		$rrdData[] = '--template ' . $dsname;
		$rrdData[] = $time . ':' . $value;
		execRRDTool($rrdData);
	}

	die(json_encode(array('success' => 'ok')));
