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

	/** Gradient for graphs. */
	$gradients = array();
	$gradients[] = 'ff0000';
	$gradients[] = 'ff0000';
	$gradients[] = 'ff0000';
	$gradients[] = 'ff0000';
	$gradients[] = 'ff1b00';
	$gradients[] = 'ff4100';
	$gradients[] = 'ff6600';
	$gradients[] = 'ff8e00';
	$gradients[] = 'ffb500';
	$gradients[] = 'ffdb00';
	$gradients[] = 'fdff00';
	$gradients[] = 'd7ff00';
	$gradients[] = 'b0ff00';
	$gradients[] = '8aff00';
	$gradients[] = '65ff00';
	$gradients[] = '3eff00';
	$gradients[] = '17ff00';
	$gradients[] = '00ff10';
	$gradients[] = '00ff36';
	$gradients[] = '00ff5c';
	$gradients[] = '00ff83';
	$gradients[] = '00ffa8';
	$gradients[] = '00ffd0';

	/**
	 * Automatically decide limits for graphs?
	 *
	 * If true, then $graphMin and $graphMax are multipliers on the min/max
	 * values to determine the scale.
	 */
	$autoLimit = false;

	// The above options can also be specified per-graph
	// using $graphOpts['<location>']['<serial>']['<option>'] = 'value';
	//
	// Options not set will use the defaults
	$graphOpts['Home']['ABCDEFGH'] = array('linearGraph' => true, 'graphMin' => 500, 'graphMax' => 2500);
	$graphOpts['Home']['ABCDEFGHI'] = array('graphMin' => 0.5, 'graphMax' => 1.75, 'autoLimit' => true);

	$probes = array();
	// $probes['Home'] = 'SomePassword';

	if (file_exists(dirname(__FILE__) . '/config.user.php')) {
		require_once(dirname(__FILE__) . '/config.user.php');
	}
