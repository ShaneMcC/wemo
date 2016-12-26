<?php
	require_once(dirname(__FILE__) . '/config.php');

	if (file_exists(dirname(__FILE__) . '/template/user/header.php')) { require_once(dirname(__FILE__) . '/template/user/header.php'); }

	// Basic Graphing to start with.
	$types = ['instantPower', 'REAL_POWER'];

	// Submit Data.
	if (file_exists($rrdDir) && is_dir($rrdDir)) {
		foreach (glob($rrdDir . '/*/*/' . $type . '.rrd') as $rrd) {
			foreach ($types as $type) {
				if (preg_match('#/([^/]+)/([^/]+)/' . $type . '.rrd#', $rrd, $m)) {
					$location = $m[1];
					$serial = $m[2];

					echo '<a href="./historicalGraphs.php?type=', urlencode($type), '&location=', urlencode($location), '&serial=', urlencode($serial), '">';
					echo '<img src="./showGraph.php?type=', urlencode($type), '&location=', urlencode($location), '&serial=', urlencode($serial), '" alt="', $type, ' for ',htmlspecialchars($location . ': ' . $serial), '">';
					echo '</a>';
				}
			}
		}
	}

	if (file_exists(dirname(__FILE__) . '/template/user/footer.php')) { require_once(dirname(__FILE__) . '/template/user/footer.php'); }
