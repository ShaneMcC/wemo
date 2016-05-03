<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	// Params we care about.
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
	$location = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
	$serial = isset($_REQUEST['serial']) ? $_REQUEST['serial'] : null;

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

	// Based on https://www.chameth.com/2016/05/02/monitoring-power-with-wemo.html
	if ($type == 'instantPower') {
		$rrdData = array();
		$rrdData[] = 'graph -';
		$rrdData[] = '--title "' . $title . '"';
		$rrdData[] = '--logarithmic --units-exponent 0';
		$rrdData[] = '--width 800 --height 500';
		$rrdData[] = '--upper-limit 2000 --lower-limit 20 --rigid';
		$rrdData[] = '--units=si';
		$rrdData[] = 'DEF:raw="' . $rrd . '":"' . $type . '":AVERAGE';
		$rrdData[] = 'CDEF:power=raw,1000,/';
		$rrdData[] = 'CDEF:powerz=power,2000,LT,power,2000,IF CDEF:powerzNoUnk=power,UN,0,powerz,IF AREA:powerzNoUnk#ff0000';
		$rrdData[] = 'CDEF:powery=power,1500,LT,power,1500,IF CDEF:poweryNoUnk=power,UN,0,powery,IF AREA:poweryNoUnk#ff0000';
		$rrdData[] = 'CDEF:powerx=power,1000,LT,power,1000,IF CDEF:powerxNoUnk=power,UN,0,powerx,IF AREA:powerxNoUnk#ff0000';
		$rrdData[] = 'CDEF:powerw=power,900,LT,power,900,IF CDEF:powerwNoUnk=power,UN,0,powerw,IF AREA:powerwNoUnk#ff0000';
		$rrdData[] = 'CDEF:powerv=power,800,LT,power,800,IF CDEF:powervNoUnk=power,UN,0,powerv,IF AREA:powervNoUnk#ff1b00';
		$rrdData[] = 'CDEF:poweru=power,700,LT,power,700,IF CDEF:poweruNoUnk=power,UN,0,poweru,IF AREA:poweruNoUnk#ff4100';
		$rrdData[] = 'CDEF:powert=power,600,LT,power,600,IF CDEF:powertNoUnk=power,UN,0,powert,IF AREA:powertNoUnk#ff6600';
		$rrdData[] = 'CDEF:powers=power,400,LT,power,400,IF CDEF:powersNoUnk=power,UN,0,powers,IF AREA:powersNoUnk#ff8e00';
		$rrdData[] = 'CDEF:powerr=power,200,LT,power,200,IF CDEF:powerrNoUnk=power,UN,0,powerr,IF AREA:powerrNoUnk#ffb500';
		$rrdData[] = 'CDEF:powerq=power,180,LT,power,180,IF CDEF:powerqNoUnk=power,UN,0,powerq,IF AREA:powerqNoUnk#ffdb00';
		$rrdData[] = 'CDEF:powerp=power,160,LT,power,160,IF CDEF:powerpNoUnk=power,UN,0,powerp,IF AREA:powerpNoUnk#fdff00';
		$rrdData[] = 'CDEF:powero=power,140,LT,power,140,IF CDEF:poweroNoUnk=power,UN,0,powero,IF AREA:poweroNoUnk#d7ff00';
		$rrdData[] = 'CDEF:powern=power,120,LT,power,120,IF CDEF:powernNoUnk=power,UN,0,powern,IF AREA:powernNoUnk#b0ff00';
		$rrdData[] = 'CDEF:powerm=power,100,LT,power,100,IF CDEF:powermNoUnk=power,UN,0,powerm,IF AREA:powermNoUnk#8aff00';
		$rrdData[] = 'CDEF:powerl=power,90,LT,power,90,IF CDEF:powerlNoUnk=power,UN,0,powerl,IF AREA:powerlNoUnk#65ff00';
		$rrdData[] = 'CDEF:powerk=power,80,LT,power,80,IF CDEF:powerkNoUnk=power,UN,0,powerk,IF AREA:powerkNoUnk#3eff00';
		$rrdData[] = 'CDEF:powerj=power,70,LT,power,70,IF CDEF:powerjNoUnk=power,UN,0,powerj,IF AREA:powerjNoUnk#17ff00';
		$rrdData[] = 'CDEF:poweri=power,60,LT,power,60,IF CDEF:poweriNoUnk=power,UN,0,poweri,IF AREA:poweriNoUnk#00ff10';
		$rrdData[] = 'CDEF:powerh=power,50,LT,power,50,IF CDEF:powerhNoUnk=power,UN,0,powerh,IF AREA:powerhNoUnk#00ff36';
		$rrdData[] = 'CDEF:powerg=power,40,LT,power,40,IF CDEF:powergNoUnk=power,UN,0,powerg,IF AREA:powergNoUnk#00ff5c';
		$rrdData[] = 'CDEF:powerf=power,30,LT,power,30,IF CDEF:powerfNoUnk=power,UN,0,powerf,IF AREA:powerfNoUnk#00ff83';
		$rrdData[] = 'CDEF:powere=power,20,LT,power,20,IF CDEF:powereNoUnk=power,UN,0,powere,IF AREA:powereNoUnk#00ffa8';
		$rrdData[] = 'CDEF:powerd=power,0,LT,power,0,IF CDEF:powerdNoUnk=power,UN,0,powerd,IF AREA:powerdNoUnk#00ffd0';
		$rrdData[] = 'LINE:power#080';

		$rrdData[] = 'VDEF:powermax=power,MAXIMUM';
		$rrdData[] = 'VDEF:poweravg=power,AVERAGE';
		$rrdData[] = 'VDEF:powermin=power,MINIMUM';

		$rrdData[] = 'COMMENT:"Maximum\: "';
		$rrdData[] = 'GPRINT:powermax:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Average\: "';
		$rrdData[] = 'GPRINT:poweravg:"%.2lfW\l"';

		$rrdData[] = 'COMMENT:"Minimum\: "';
		$rrdData[] = 'GPRINT:powermin:"%.2lfW\l"';

		$out = execRRDTool($rrdData);
		header("Content-Type: image/png");
		die($out['stdout']);
	}

	die('Internal Error.');
