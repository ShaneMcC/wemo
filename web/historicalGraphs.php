<?php
	require_once(dirname(__FILE__) . '/config.php');

	if (file_exists(dirname(__FILE__) . '/template/user/header.php')) { require_once(dirname(__FILE__) . '/template/user/header.php'); }

	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

	if (!isset($historicalOptions)) {
		$historicalOptions = ['1 Day' => ['days' => 1],
		                      '10 Days' => ['days' => 10],
		                      'One Month' => ['days' => 30],
		                      'One Year' => ['days' => 360],
		                     ];
	}

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
	if (isset($graphOpts[$location][$serial]['title_' . $type])) { $title = $graphOpts[$location][$serial]['title_' . $type]; }

	echo '<h1>';
	echo htmlspecialchars($title);
	echo '</h1>';
	echo '<a href="./">[ Back to all graphs ]</a>';
	echo '<hr>';

	foreach ($historicalOptions as $name => $setting) {
		$days = isset($setting['days']) ? $setting['days'] : '';
		$step = isset($setting['step']) ? $setting['step'] : '';

		echo '<h2>', htmlspecialchars($name), '</h2>';
		echo '<img src="./showGraph.php?type=', urlencode($type), '&location=', urlencode($location), '&serial=', urlencode($serial), '&days=', urlencode($days), '&step=', urlencode($step), '" alt="', htmlspecialchars($name) , ' - ', htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial), '">';
		echo '<hr>';
	}

	if (file_exists(dirname(__FILE__) . '/template/user/footer.php')) { require_once(dirname(__FILE__) . '/template/user/footer.php'); }
