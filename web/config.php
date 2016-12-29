<?php

	/** RRD storage directory. */
	$rrdDir = dirname(__FILE__) . '/rrds/';

	/** Path to rrdtool binary */
	$rrdtool = '/usr/bin/rrdtool';

	/** Default Graph Width. */
	$graphWidth = 800;

	/** Default Graph Height. */
	$graphHeight = 500;

	/** Should graph be linear rather than exponential? */
	$linearGraph = false;

	/** Allow showGraphn debug outputs to be used. */
 	$allowDebug = true;

	/** Lower Limit for graphs. */
	$graphMin = 20;

	/** Upper Limit for graphs. */
	$graphMax = 2000;

	/** Default graph steps */
	$graphSteps = 1;

	/** Default graph start */
	$graphStart = '-1 day';

	/** Line colour on top of the gradient */
	$lineColour = '#000000';

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
	 * Extra options for rrdtool.
	 *
	 * This should be an array of additional options to pass.
	 *
	 * There are 3 places that additional parameters can be passed:
	 *   - flags: This will add the options after the initial flags, before
	 *            the DEF/CDEF/VDEFs.
	 *   - defs: This will add the options after all the DEF/CDEF/VDEFs
	 *   - end: This will add the options after the closing comments.
	 *
	 * Example: $rrdoptions[<graphtype>]['flags'] = array('--slope-mode', '--graph-render-mode mono');
	 */
	$rrdoptions['instantPower']['flags'] = array();
	$rrdoptions['instantPower']['defs'] = array();
	$rrdoptions['instantPower']['end'] = array();

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

	// It is also possible to specify additional parameters to pass to RRDTOOL
	// when drawing the graph.
	//
	// This should be an array of additional options to pass.
	//
	// These will be used INSTEAD OF $rrdoptions values where specified.
	$graphOpts['Home']['ABCDEFGH']['rrd_flags_instantPower'] = array('--slope-mode', '--graph-render-mode mono');

	// Graphs to display in historical view.
	$historicalOptions = ['1 Day' => ['start' => '-1 days'],
	                      '10 Days' => ['start' => '-10 days'],
	                      'One Month' => ['start' => '-1 month'],
	                      'One Year' => ['start' => '-1 year'],
	                     ];

	$probes = array();
	// $probes['Home'] = 'SomePassword';

	if (file_exists(dirname(__FILE__) . '/config.user.php')) {
		require_once(dirname(__FILE__) . '/config.user.php');
	}
