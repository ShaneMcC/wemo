#!/usr/bin/php
<?php

	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/ssdp.php');
	require_once(dirname(__FILE__) . '/phpuri.php');
	require_once(dirname(__FILE__) . '/cliparams.php');

	addCLIParam('s', 'search', 'Just search for devices, don\'t collect or post any data.');
	addCLIParam('p', 'post', 'Just post stored data to collector, don\'t collect any new data.');
	addCLIParam('d', 'debug', 'Don\'t post data to collector, just dump to CLI instead.');
	addCLIParam('', 'key', 'Submission to key use rather than config value', true);
	addCLIParam('', 'location', 'Submission location to use rather than config value', true);
	addCLIParam('', 'server', 'Submission server to use rather than config value', true);
	addCLIParam('', 'ip', 'Discovery IP to probe rather than config value', true);
	addCLIParam('', 'allow-unicast-discovery', 'SSDP discovery to non-multicast IPs should have unicast headers.');
	addCLIParam('', 'no-unicast-discovery', 'SSDP discovery to non-multicast IPs should have multicast headers');
	addCLIParam('', 'timeout', 'SSDP discovery timeout in seconds.', true);

	$daemon['cli'] = parseCLIParams($_SERVER['argv']);
	if (isset($daemon['cli']['help'])) {
		echo 'Usage: ', $_SERVER['argv'][0], ' [options]', "\n\n";
		echo 'Options:', "\n\n";
		echo showCLIParams(), "\n";
		die(0);
	}

	if (isset($daemon['cli']['key'])) { $submissionKey = end($daemon['cli']['key']['values']); }
	if (isset($daemon['cli']['location'])) { $location = end($daemon['cli']['location']['values']); }
	if (isset($daemon['cli']['server'])) { $collectionServer = end($daemon['cli']['server']['values']); }
	if (isset($daemon['cli']['ip'])) { $discoveryIPs = $daemon['cli']['ip']['values']; }
	if (isset($daemon['cli']['timeout'])) { $ssdpTimeout = end($daemon['cli']['timeout']['values']); }
	if (isset($daemon['cli']['allow-unicast-discovery'])) { $allowUnicastDiscovery = true; }
	if (isset($daemon['cli']['no-unicast-discovery'])) { $allowUnicastDiscovery = false; }

	$time = time();

	$ssdp = new SSDP($discoveryIPs);
	$devices = array();

	$insightService = 'urn:Belkin:service:insight:1';

	if (!isset($daemon['cli']['post'])) {
		foreach ($ssdp->search($insightService, $ssdpTimeout, $allowUnicastDiscovery) as $device) {
			$loc = file_get_contents($device['location']);
			$xml = simplexml_load_string($loc);

			$dev = array();
			$dev['name'] = (String)$xml->device->friendlyName;
			$dev['serial'] = (String)$xml->device->serialNumber;
			$dev['ip'] = $device['__IP'];
			$dev['port'] = $device['__PORT'];

			$dev['data'] = array();

			echo sprintf('Found: %s / %s [%s:%s -> %s]' . "\n", $dev['name'], $dev['serial'], $dev['ip'], $dev['port'], $device['location']);

			if (isset($daemon['cli']['search'])) { continue; }
			foreach ($xml->device->serviceList->service as $service) {
				if ($service->serviceType == $insightService) {
					$url = phpUri::parse($device['location'])->join($service->controlURL);

					$soap = new SoapClient(null, array('location' => $url, 'uri' => $insightService));

					$calls = array();
					$calls['instantPower'] = 'GetPower';
					$calls['todayKWH'] = 'GetTodayKWH';
					$calls['powerThreshold'] = 'GetPowerThreshold';
					$calls['insightInfo'] = 'GetInsightInfo';
					$calls['insightParams'] = 'GetInsightParams';
					$calls['onFor'] = 'GetONFor';
					$calls['inSBYSince'] = 'GetInSBYSince';
					$calls['todayONTime'] = 'GetTodayONTime';
					$calls['todaySBYTime'] = 'GetTodaySBYTime';

					foreach ($calls as $k => $f) {
						$dev['data'][$k] = $soap->__soapCall($f, array());
					}
				}
			}

			$devices[] = $dev;
		}

		if (count($devices) > 0) {
			$data = json_encode(array('time' => $time, 'devices' => $devices));
			if (!file_exists($dataDir)) { @mkdir($dataDir); }
			if (file_exists($dataDir) && is_dir($dataDir)) {
				file_put_contents($dataDir . '/' . $time . '.js', $data);
			}
		}
	}

	function submitData($data) {
		global $location, $submissionKey, $collectionServer;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $collectionServer);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $location . ':' . $submissionKey);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$result = curl_exec($ch);
		curl_close($ch);

		$result = @json_decode($result, true);
		return isset($result['success']);
	}

	if (isset($daemon['cli']['search'])) { die(0); }
	if (isset($daemon['cli']['debug'])) {
		print_r($devices);
		die(0);
	}

	// Submit Data.
	if (file_exists($dataDir) && is_dir($dataDir)) {
		foreach (glob($dataDir . '/*.js') as $dataFile) {
			$data = file_get_contents($dataFile);
			$test = json_decode($data, true);
			if (isset($test['time']) && isset($test['devices'])) {
				if (submitData($data)) {
					echo 'Submitted data for: ', $test['time'], "\n";
					unlink($dataFile);
				}
			}
		}
	}
