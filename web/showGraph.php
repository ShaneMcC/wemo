<?php
	$pageName = 'showGraph';
	$graphPage = isset($_REQUEST['graphPage']) ? $_REQUEST['graphPage'] : $pageName;
	$graphCustom = isset($_REQUEST['graphCustom']) ? $_REQUEST['graphCustom'] : '';

	$noTitle = isset($_REQUEST['noTitle']);
	$noAxis = isset($_REQUEST['noAxis']);
	$noComments = isset($_REQUEST['noComments']);

	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	// Params we care about
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;
	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : $graphStart;
	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : null;
	$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : $graphSteps;

	$debug = isset($_REQUEST['debug']) && $allowDebug;
	$debugOut = isset($_REQUEST['debugOut']) && $allowDebug;

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

	$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : null;
	$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : null;

	if ($height == null) { $height = getGraphOption($location, $serial, 'graphHeight', $height); }
	if ($width == null) { $width = getGraphOption($location, $serial, 'graphWidth', $width); }

	if ($height == null) { $height = $graphHeight; }
	if ($width == null) { $width = $graphWidth; }

	$graphMin = getGraphOption($location, $serial, 'graphMin', $graphMin);
	$graphMax = getGraphOption($location, $serial, 'graphMax', $graphMax);
	$autoLimit = getGraphOption($location, $serial, 'autoLimit', $autoLimit);
	$linearGraph = getGraphOption($location, $serial, 'linearGraph', $linearGraph);
	$title = getGraphOption($location, $serial, 'title_' . $type, $title);
	$showDataComments = getGraphOption($location, $serial, 'dataComments', $showDataComments);

	// Originally based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
	if ($type == 'instantPower' || $type == 'REAL_POWER') {
		if ($autoLimit) {
			$rrdData = array();
			$rrdData[] = 'graph /dev/null';

			if ($start !== null && !empty($start)) { $rrdData[] = '--start ' . escapeshellarg($start); }
			if ($end !== null && !empty($end)) { $rrdData[] = '--end ' . escapeshellarg($end); }
			if (preg_match('#^[0-9]+$#', $step)) { $rrdData[] = '--step ' . (int)$step; }
			$rrdData[] = '--width ' . (int)$width . ' --height ' . (int)$height;

			$rrdData[] = 'DEF:raw="' . $rrd . '":"' . $type . '":AVERAGE';
			if ($type == 'instantPower') {
				$rrdData[] = 'CDEF:power=raw,1000,/';
			} else if ($type == 'REAL_POWER') {
				$rrdData[] = 'CDEF:power=raw';
			}
			$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
			$rrdData[] = 'VDEF:powermin=power,MINIMUM';
			$rrdData[] = 'PRINT:powermin:"%lf"';
			$rrdData[] = 'PRINT:powermax:"%lf"';
			$out = execRRDTool($rrdData);
			$bits = explode("\n", $out['stdout']);
			$lowerLimit = max(getLowerLimit($bits[1]), 0);
			$upperLimit = max(getUpperLimit($bits[2]), 1);
		} else {
			$lowerLimit = $graphMin;
			$upperLimit = $graphMax;
		}


		$rrdData = array();
		$rrdData[] = 'graph -';
		if (!$noTitle) { $rrdData[] = '--title "' . $title . '"'; }
		if (!$linearGraph) {
			$rrdData[] = '--logarithmic --units-exponent 0';
		}

		if ($start !== null && !empty($start)) { $rrdData[] = '--start ' . escapeshellarg($start); }
		if ($end !== null && !empty($end)) { $rrdData[] = '--end ' . escapeshellarg($end); }

		if (preg_match('#^[0-9]+$#', $step)) { $rrdData[] = '--step ' . (int)$step; }

		$rrdData[] = '--width ' . (int)$width . ' --height ' . (int)$height;
		$rrdData[] = '--upper-limit ' . ceil($upperLimit);
		$rrdData[] = '--lower-limit ' . floor($lowerLimit);
		$rrdData[] = '--rigid';
		if ($noAxis) {
			$rrdData[] = '--y-grid none --x-grid none';
		} else {
			$rrdData[] = '--vertical-label "Watts"';
		}
		$rrdData[] = '--units=si';

		$rrdData = array_merge($rrdData, getCustomSettings($location, $serial, $type, 'flags'));

		$rrdData[] = 'DEF:raw="' . $rrd . '":"' . $type . '":AVERAGE';
		if ($type == 'instantPower') {
			$rrdData[] = 'CDEF:power=raw,1000,/';
		} else if ($type == 'REAL_POWER') {
			$rrdData[] = 'CDEF:power=raw';
		}

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

		if (isset($lineColour)) { $rrdData[] = 'LINE:power' . $lineColour; }

		$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
		$rrdData[] = 'VDEF:poweravg=power,AVERAGE';
		$rrdData[] = 'VDEF:powermin=power,MINIMUM';
		$rrdData[] = 'VDEF:powerlast=power,LAST';

		$rrdData = array_merge($rrdData, getCustomSettings($location, $serial, $type, 'defs'));

		if (!$noComments && $showDataComments) {
			$rrdData[] = 'COMMENT:"Maximum\: "';
			$rrdData[] = 'GPRINT:powermax:"%.2lfW\l"';

			$rrdData[] = 'COMMENT:"Average\: "';
			$rrdData[] = 'GPRINT:poweravg:"%.2lfW\l"';

			$rrdData[] = 'COMMENT:"Minimum\: "';
			$rrdData[] = 'GPRINT:powermin:"%.2lfW\l"';

			$rrdData[] = 'COMMENT:"Latest\:  "';
			$rrdData[] = 'GPRINT:powerlast:"%.2lfW\l"';
		}

		$rrdData = array_merge($rrdData, getCustomSettings($location, $serial, $type, 'end'));

		if ($debug) {
			$debugRRDData = $rrdData;
			array_walk_recursive($debugRRDData, function(&$value) { $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); });
			echo '<pre>', print_r($debugRRDData, true);
			if (!$debugOut) { die(); }
		}

		$out = execRRDTool($rrdData);

		function isPNG($data) {
			return count(array_diff(unpack('C8', $data), [137, 80, 78, 71, 13, 10, 26, 10])) == 0;
		}

		if ($debugOut) {
			if (isPNG($out['stdout'])) { $out['stdout'] == '<PNG IMAGE>'; }
			array_walk_recursive($out, function(&$value) { $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); });
			print_r($out);
			die();
		} else {
			header("Content-Type: image/png");
			if (isPNG($out['stdout'])) {
				die($out['stdout']);
			} else {
				$img = imagecreate($width, $height);
				$bg = imagecolorallocate($img, 200, 200, 200);
				$text = imagecolorallocate($img, 200, 0, 0);
				imagestring($img, 4, 20, 20, 'rrdtool Error', $text);
				imagestring($img, 4, 20, 35, trim($out['stdout']), $text);
				imagepng($img);
				imagecolordeallocate($bg);
				imagecolordeallocate($text);
				imagedestroy($img);
				die();
			}
		}
	}

	die('Internal Error.');
