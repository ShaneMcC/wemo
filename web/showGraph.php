<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	// Params we care about.
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

	$debug = isset($_REQUEST['debug']);

	// TODO: Decide sane limits automatically.
	$linearGraph = false;
	$lowerLimit = 20;
	$upperLimit = 2000;

	// If the params are not passed to us, abort.
	if ($type == null || $location == null || $serial == null) { die('Internal Error.'); }

	// If the params look dodgy, abort.
	if (preg_match('#[^A-Z0-9-_ ]#i', $location) || preg_match('#[^A-Z0-9-_]#i', $serial)) { die('Internal Error.'); }

	$dir = $rrdDir . '/' . $location . '/' . $serial;
	$rrd = $dir . '/' . $type . '.rrd';
	$meta = $dir . '/meta.js';

	$title = $type . ' for ' . $location . ': ' . $serial;
	if (file_exists($meta)) {
		$meta = @json_decode(file_get_contents($meta), true);
		if ($meta !== null) {
			$title = $type . ' for ' . $location . ': ' . $meta['name'];
		}
	}

	// Based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
	if ($type == 'instantPower') {
		$rrdData = array();
		$rrdData[] = 'graph -';
		$rrdData[] = '--title "' . $title . '"';
		if (!$linearGraph) {
			$rrdData[] = '--logarithmic --units-exponent 0';
		}
		$rrdData[] = '--width 800 --height 500';
		$rrdData[] = '--upper-limit ' . $upperLimit;
		$rrdData[] = '--lower-limit ' . $lowerLimit;
		$rrdData[] = '--rigid';
		$rrdData[] = '--units=si';
		$rrdData[] = 'DEF:raw="' . $rrd . '":"' . $type . '":AVERAGE';
		$rrdData[] = 'CDEF:power=raw,1000,/';

		$gradients[] = 'ff0000';
		$gradients[] = 'ff0000';
		$gradients[] = 'ff0000';
		$gradients[] = 'ff0000';
		$gradients[] = 'ff1b00';
		$gradients[] = 'ff4100';
		$gradients[] = 'ff6600';
		$gradients[] = 'ff8e00';
		$gradients[] = 'ffb500';
		$gradients[] = 'ffdb00';
		$gradients[] = 'fdff00';
		$gradients[] = 'd7ff00';
		$gradients[] = 'b0ff00';
		$gradients[] = '8aff00';
		$gradients[] = '65ff00';
		$gradients[] = '3eff00';
		$gradients[] = '17ff00';
		$gradients[] = '00ff10';
		$gradients[] = '00ff36';
		$gradients[] = '00ff5c';
		$gradients[] = '00ff83';
		$gradients[] = '00ffa8';
		$gradients[] = '00ffd0';

		$i = count($gradients);
		foreach ($gradients as $val => $col) {
			if ($linearGraph) {
				$val = floor($lowerLimit + (($upperLimit - $lowerLimit)/ count($gradients) * $i--));
			} else {
				$val = floor(pow(10, ($i-- * (log($upperLimit/$lowerLimit, 10))/count($gradients))) * $lowerLimit);
			}
			$rrdData[] = 'CDEF:power' . $val . '=power,' . $val . ',LT,power,' . $val . ',IF CDEF:power' . $val . 'NoUnk=power,UN,0,power' . $val . ',IF AREA:power' . $val . 'NoUnk#' . $col;
		}

		$rrdData[] = 'LINE:power#080';

		$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
		$rrdData[] = 'VDEF:poweravg=power,AVERAGE';
		$rrdData[] = 'VDEF:powermin=power,MINIMUM';

		$rrdData[] = 'COMMENT:"Maximum\: "';
		$rrdData[] = 'GPRINT:powermax:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Average\: "';
		$rrdData[] = 'GPRINT:poweravg:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Minimum\: "';
		$rrdData[] = 'GPRINT:powermin:"%.2lfW\l"';

		if ($debug) { die('<pre>'.print_r($rrdData, true)); }

		$out = execRRDTool($rrdData);
		header("Content-Type: image/png");
		die($out['stdout']);
	}

	die('Internal Error.');
