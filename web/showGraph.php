<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	// Params we care about.
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

	$debug = isset($_REQUEST['debug']);

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

	if (isset($graphOpts[$location][$serial]['graphMin'])) { $graphMin = $graphOpts[$location][$serial]['graphMin']; }
	if (isset($graphOpts[$location][$serial]['graphMax'])) { $graphMax = $graphOpts[$location][$serial]['graphMax']; }
	if (isset($graphOpts[$location][$serial]['autoLimit'])) { $autoLimit = $graphOpts[$location][$serial]['autoLimit']; }
	if (isset($graphOpts[$location][$serial]['linearGraph'])) { $linearGraph = $graphOpts[$location][$serial]['linearGraph']; }
	if (isset($graphOpts[$location][$serial]['title_' . $type])) { $title = $graphOpts[$location][$serial]['title_' . $type]; }

	// Originally based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
	if ($type == 'instantPower') {
		if ($autoLimit) {
			$rrdData = array();
			$rrdData[] = 'graph /dev/null';
			$rrdData[] = 'DEF:raw="' . $rrd . '":"' . $type . '":AVERAGE';
			$rrdData[] = 'CDEF:power=raw,1000,/';
			$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
			$rrdData[] = 'VDEF:powermin=power,MINIMUM';
			$rrdData[] = 'PRINT:powermin:"%lf"';
			$rrdData[] = 'PRINT:powermax:"%lf"';
			$out = execRRDTool($rrdData);
			$bits = explode("\n", $out['stdout']);
			$lowerLimit = max(floor($bits[1]) * $graphMin, 1);
			$upperLimit = max(ceil($bits[2]) * $graphMax, 1);
		} else {
			$lowerLimit = $graphMin;
			$upperLimit = $graphMax;
		}


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

		$i = count($gradients);
		foreach ($gradients as $val => $col) {
			if ($linearGraph) {
				$val = $lowerLimit + (($upperLimit - $lowerLimit)/ count($gradients) * $i--);
			} else {
				$val = pow(10, ($i-- * (log($upperLimit/$lowerLimit, 10))/count($gradients))) * $lowerLimit;
			}
			$val = ($i == 0) ? floor($val) : ceil($val);

			$rrdData[] = 'CDEF:powerArea' . $i . '=power,' . $val . ',LT,power,' . $val . ',IF CDEF:powerArea' . $i . 'NoUnk=power,UN,0,powerArea' . $i . ',IF AREA:powerArea' . $i . 'NoUnk#' . $col;
		}

		$rrdData[] = 'LINE:power#080';

		$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
		$rrdData[] = 'VDEF:poweravg=power,AVERAGE';
		$rrdData[] = 'VDEF:powermin=power,MINIMUM';
		$rrdData[] = 'VDEF:powerlast=power,LAST';

		$rrdData[] = 'COMMENT:"Maximum\: "';
		$rrdData[] = 'GPRINT:powermax:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Average\: "';
		$rrdData[] = 'GPRINT:poweravg:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Minimum\: "';
		$rrdData[] = 'GPRINT:powermin:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Latest\:  "';
		$rrdData[] = 'GPRINT:powerlast:"%.2lfW\l"';

		if ($debug) { die('<pre>'.print_r($rrdData, true)); }

		$out = execRRDTool($rrdData);
		header("Content-Type: image/png");
		die($out['stdout']);
	}

	die('Internal Error.');
