<?php

	/** RRD storage directory. */
	$rrdDir = dirname(__FILE__) . '/rrds/';

	/** Path to rrdtool binary */
	$rrdtool = '/usr/bin/rrdtool';

	$probes = array();
	// $probes['Home'] = 'SomePassword';

	if (file_exists(dirname(__FILE__) . '/config.user.php')) {
		require_once(dirname(__FILE__) . '/config.user.php');
	}
