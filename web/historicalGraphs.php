<?php
	$pageName = 'historicalGraphs';
	require_once(dirname(__FILE__) . '/config.php');

	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '';
	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '';

	if (!isset($historicalOptions)) {
		$historicalOptions = ['1 Day' => ['start' => '-1 days'],
		                      '10 Days' => ['start' => '-10 days'],
		                      'One Month' => ['start' => '-1 month'],
		                      'One Year' => ['start' => '-1 year'],
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

	if (file_exists(dirname(__FILE__) . '/template/user/header.php')) { require_once(dirname(__FILE__) . '/template/user/header.php'); }

	echo '<h1>';
	echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($title);
	echo '</h1>';
	echo '<a href="./">[ Back to all graphs ]</a>';
	echo '<hr>';

	echo '<form method="GET" style="display: inline">';
	foreach ($_REQUEST as $k => $v) {
		if ($k != 'start' && $k != 'end') {
			echo '  <input type="hidden" name="', htmlspecialchars($k), '" value="', htmlspecialchars($v),'">';
		}
	}
	echo '  Start: ';
	echo '  <input type="text" name="start" value="', htmlspecialchars($start),'">';
	echo '  End:';
	echo '  <input type="text" name="end" value="', htmlspecialchars($end),'">';
	echo '  <input type="submit" value="Submit">';
	echo '</form>';

	echo '<form method="GET" style="display: inline">';
	foreach ($_REQUEST as $k => $v) {
		if ($k != 'start' && $k != 'end') {
			echo '  <input type="hidden" name="', htmlspecialchars($k), '" value="', htmlspecialchars($v),'">';
		}
	}
	echo '  <input type="submit" value="Reset">';
	echo '</form>';
	echo '<hr>';

	$typeClass = 'type_' . preg_replace('#[^a-z0-9]#i', '', $type);
	$serialClass = 'serial_' . preg_replace('#[^a-z0-9]#i', '', $serial);

	if ($start !== '' || $end !== '') {
		$options = [];
		$options['type'] = $type;
		$options['location'] = $location;
		$options['serial'] = $serial;
		$options['graphPage'] = $pageName;
		if ($start !== '') { $options['start'] = $start; }
		if ($end !== '') { $options['end'] = $end; }
		if (isset($_REQUEST['step'])) { $options['end'] = $_REQUEST['step']; }

		echo '<h2>', htmlspecialchars($start), ' to ', htmlspecialchars($end), '</h2>';
		echo '<img class="graph historical historical_custom ', $typeClass, ' ', $serialClass, '" src="./showGraph.php?', http_build_query($options), '" alt="', htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial), '">';
		echo '<hr>';
	} else {
		foreach ($historicalOptions as $name => $setting) {
			$start = isset($setting['start']) ? $setting['start'] : '';
			$step = isset($setting['step']) ? $setting['step'] : '';

			$options = [];
			$options['type'] = $type;
			$options['location'] = $location;
			$options['serial'] = $serial;
			$options['graphPage'] = $pageName;
			if (!empty($start)) { $options['start'] = $start; }
			if (!empty($step)) { $options['step'] = $step; }

			$nameClass = 'historical_' . preg_replace('#[^a-z0-9]#i', '', $name);
			echo '<h2>', htmlspecialchars($name), '</h2>';
			echo '<img class="graph historical ', $nameClass, ' ', $typeClass, ' ', $serialClass, '" src="./showGraph.php?', http_build_query($options), '" alt="', htmlspecialchars($name) , ' - ', htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial), '">';
			echo '<hr>';
		}
	}

	if (file_exists(dirname(__FILE__) . '/template/user/footer.php')) { require_once(dirname(__FILE__) . '/template/user/footer.php'); }
