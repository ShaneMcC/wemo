<?php
	require_once(dirname(__FILE__) . '/config.php');

	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '';
	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '';

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

	$pageName = 'historicalGraphs';
	if (file_exists(dirname(__FILE__) . '/template/user/header.php')) { require_once(dirname(__FILE__) . '/template/user/header.php'); }

	echo '<h1>';
	echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($title);
	echo '</h1>';
	echo '<a href="./">[ Back to all graphs ]</a>';
	echo '<hr>';

	echo '<form method="GET">';
	echo '  Start:<br>';
	echo '  <input type="text" name="start"><br>';
	echo '  End:<br>';
	echo '  <input type="text" name="end">';
	echo '</form>';
	echo '<hr>';

	if ($start !== '' || $end !== '') {
		$options = [];
		$options['type'] = $type;
		$options['location'] = $location;
		$options['serial'] = $serial;
		if ($start !== '') { $options['start'] = $start; }
		if ($end !== '') { $options['end'] = $end; }
		if (isset($_REQUEST['step'])) { $options['end'] = $_REQUEST['step']; }

		echo '<h2>', htmlspecialchars($start), ' to ', htmlspecialchars($end), '</h2>';
		echo '<img src="./showGraph.php?', http_build_query($options), '" alt="', htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial), '">';
		echo '<hr>';
	} else {
		foreach ($historicalOptions as $name => $setting) {
			$days = isset($setting['days']) ? $setting['days'] : '';
			$step = isset($setting['step']) ? $setting['step'] : '';

			$options = [];
			$options['type'] = $type;
			$options['location'] = $location;
			$options['serial'] = $serial;
			$options['days'] = $days;
			$options['step'] = $step;

			echo '<h2>', htmlspecialchars($name), '</h2>';
			echo '<img src="./showGraph.php?', http_build_query($options), '" alt="', htmlspecialchars($name) , ' - ', htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial), '">';
			echo '<hr>';
		}
	}

	if (file_exists(dirname(__FILE__) . '/template/user/footer.php')) { require_once(dirname(__FILE__) . '/template/user/footer.php'); }
