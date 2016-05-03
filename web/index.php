<?php
	require_once(dirname(__FILE__) . '/config.php');

	// Basic Graphing to start with.

	$type = 'instantPower';

	// Submit Data.
	if (file_exists($rrdDir) && is_dir($rrdDir)) {
		foreach (glob($rrdDir . '/*/*/' . $type . '.rrd') as $rrd) {
			if (preg_match('#/([^/]+)/([^/]+)/' . $type . '.rrd#', $rrd, $m)) {
				$location = $m[1];
				$serial = $m[2];

				echo '<img src="./showGraph.php?type=', urlencode($type), '&location=', urlencode($location), '&serial=', urlencode($serial), '" alt="', $type, ' for ',htmlspecialchars($location . ': ' . $serial), '">';
			}
		}
	}
