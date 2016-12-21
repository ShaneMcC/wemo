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

	echo '<h1>';
	echo htmlspecialchars($type), ' for ', htmlspecialchars($location . ': ' . $serial);
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
