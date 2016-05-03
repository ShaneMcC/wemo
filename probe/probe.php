<?php

	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/ssdp.php');
	require_once(dirname(__FILE__) . '/phpuri.php');

	$time = time();

	$ssdp = new SSDP();
	$devices = array();

	$insightService = 'urn:Belkin:service:insight:1';

	foreach ($ssdp->search($insightService, 5) as $device) {
		$loc = file_get_contents($device['location']);
		$xml = simplexml_load_string($loc);

		$dev = array();
		$dev['name'] = (String)$xml->device->friendlyName;
		$dev['serial'] = (String)$xml->device->serialNumber;
		$dev['data'] = array();

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
