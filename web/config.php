<?php

	/** RRD storage directory. */
	$rrdDir = dirname(__FILE__) . '/rrds/';

	/** Path to rrdtool binary */
	$rrdtool = '/usr/bin/rrdtool';

	/** Should graph be linear rather than exponential? */
	$linearGraph = false;

	/** Lower Limit for graphs. */
	$graphMin = 20;

	/** Upper Limit for graphs. */
	$graphMax = 2000;

	/**
	 * Automatically decide limits for graphs?
	 *
	 * If true, then $graphMin and $graphMax are multipliers on the min/max
	 * values to determine the scale.
	 */
	$autoLimit = false;

	$probes = array();
	// $probes['Home'] = 'SomePassword';

	if (file_exists(dirname(__FILE__) . '/config.user.php')) {
		require_once(dirname(__FILE__) . '/config.user.php');
	}
