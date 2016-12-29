<?php
	require_once(dirname(__FILE__) . '/config.php');

	$pageName = 'index';
	if (file_exists(dirname(__FILE__) . '/template/user/header.php')) { require_once(dirname(__FILE__) . '/template/user/header.php'); }

	// Basic Graphing to start with.
	$types = ['instantPower', 'REAL_POWER'];

	// Submit Data.
	if (file_exists($rrdDir) && is_dir($rrdDir)) {
		foreach ($types as $type) {
			foreach (glob($rrdDir . '/*/*/' . $type . '.rrd') as $rrd) {
				if (preg_match('#/([^/]+)/([^/]+)/' . $type . '.rrd#', $rrd, $m)) {
					$location = $m[1];
					$serial = $m[2];

					$options = [];
					$options['type'] = $type;
					$options['location'] = $location;
					$options['serial'] = $serial;

					$typeClass = 'type_' . preg_replace('#[^a-z0-9]#i', $type);
					$serialClass = 'serial_' . preg_replace('#[^a-z0-9]#i', $serial);

					echo '<a href="./historicalGraphs.php?', http_build_query($options), '">';
					echo '<img class="graph ', $typeClass, ' ', $serialClass, '" src="./showGraph.php?', http_build_query($options), '" alt="', $type, ' for ', htmlspecialchars($location . ': ' . $serial), '">';
					echo '</a>';
				}
			}
		}
	}

	if (file_exists(dirname(__FILE__) . '/template/user/footer.php')) { require_once(dirname(__FILE__) . '/template/user/footer.php'); }
