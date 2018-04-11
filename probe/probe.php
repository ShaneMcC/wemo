#!/usr/bin/php
<?php

	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/ssdp.php');
	require_once(dirname(__FILE__) . '/phpuri.php');
	require_once(dirname(__FILE__) . '/cliparams.php');

	addCLIParam('s', 'search', 'Just search for devices, don\'t collect or post any data.');
	addCLIParam('p', 'post', 'Just post stored data to collector, don\'t collect any new data.');
	addCLIParam('d', 'debug', 'Don\'t save data or attempt to post to collector, just dump to CLI instead.');
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
	if (isset($daemon['cli']['server'])) { $collectionServer = $daemon['cli']['server']['values']; }
	if (isset($daemon['cli']['ip'])) { $discoveryIPs = $daemon['cli']['ip']['values']; }
	if (isset($daemon['cli']['timeout'])) { $ssdpTimeout = end($daemon['cli']['timeout']['values']); }
	if (isset($daemon['cli']['allow-unicast-discovery'])) { $allowUnicastDiscovery = true; }
	if (isset($daemon['cli']['no-unicast-discovery'])) { $allowUnicastDiscovery = false; }

	if (!is_array($collectionServer)) { $collectionServer = array($collectionServer); }

	$time = time();

	$ssdp = new SSDP($discoveryIPs);
	$devices = array();

	$insightService = 'urn:Belkin:service:insight:1';

	if (!isset($daemon['cli']['post'])) {
		foreach ($ssdp->search($insightService, $ssdpTimeout, $allowUnicastDiscovery) as $device) {
			$loc = @file_get_contents($device['location']);
			$xml = simplexml_load_string($loc);
			if ($xml === FALSE) { continue; }

			$dev = array();
			$dev['name'] = (String)$xml->device->friendlyName;
			$dev['serial'] = (String)$xml->device->serialNumber;
			$dev['ip'] = $device['__IP'];
			$dev['port'] = $device['__PORT'];

			$dev['data'] = array();

			echo sprintf('Found: %s / %s [%s:%s -> %s]' . "\n", $dev['name'], $dev['serial'], $dev['ip'], $dev['port'], $device['location']);

			if (isset($daemon['cli']['search'])) { continue; }
			if (!isset($xml->device->serviceList->service)) { continue; }
				foreach ($xml->device->serviceList->service as $service) {
				if (!isset($service->serviceType) || !isset($service->controlURL)) { continue; }
				$url = phpUri::parse($device['location'])->join($service->controlURL);
				$dev['services'][(string)$service->serviceType] = $url;

				if ($service->serviceType == $insightService) {
					$url = phpUri::parse($device['location'])->join($service->controlURL);

					$soap = new SoapClient(null, array('location' => $url, 'uri' => $insightService));

					$calls = array();
					$calls['insightParams'] = 'GetInsightParams';
					$calls['instantPower'] = 'GetPower';
					$calls['todayKWH'] = 'GetTodayKWH';
					$calls['powerThreshold'] = 'GetPowerThreshold';
					$calls['insightInfo'] = 'GetInsightInfo';
					$calls['onFor'] = 'GetONFor';
					$calls['inSBYSince'] = 'GetInSBYSince';
					$calls['todayONTime'] = 'GetTodayONTime';
					$calls['todaySBYTime'] = 'GetTodaySBYTime';

					foreach ($calls as $k => $f) {
						try {
							$dev['data'][$k] = $soap->__soapCall($f, array());
						} catch (Exception $e) { }
					}

					// Newwer firmware doesn't seem to like the answering to
					// all of the above functions all of the time.
					//
					// However, it does seem to always answer insightParams.
					//
					// So now we parse insightParams...
					//
					// Based on http://ouimeaux.readthedocs.io/en/latest/_modules/ouimeaux/device/insight.html
					// also http://home.stockmopar.com/wemo-insight-hacking/
					// and https://github.com/openhab/openhab/blob/master/bundles/binding/org.openhab.binding.wemo/src/main/java/org/openhab/binding/wemo/internal/WemoBinding.java
					if (isset($dev['data']['insightParams'])) {
						$bits = explode('|', $dev['data']['insightParams']);
						$dev['data']['insightParams_state'] = $bits[0];
						$dev['data']['insightParams_lastChange'] = $bits[1];
						$dev['data']['insightParams_onFor'] = $bits[2];
						$dev['data']['insightParams_onToday'] = $bits[3];
						$dev['data']['insightParams_onTotal'] = $bits[4];
						$dev['data']['insightParams_timeperiod'] = $bits[5];
						$dev['data']['insightParams_averagePower'] = $bits[6];
						$dev['data']['insightParams_currentMW'] = $bits[7];
						$dev['data']['insightParams_todayMW'] = $bits[8];
						$dev['data']['insightParams_totalMW'] = $bits[9];
						$dev['data']['insightParams_threshold'] = $bits[10];
					}

					// And then where we didn't get anything from the real
					// function calls, and there is an appropriate entry in
					// insightParams, we'll simulate that instead... Stupid.
					$map = array();
					$map['instantPower'] = 'insightParams_currentMW';
					$map['powerThreshold'] = 'insightParams_threshold';
					$map['onFor'] = 'insightParams_onFor';
					$map['todayONTime'] = 'insightParams_onToday';

					foreach ($map as $k => $v) {
						if (!isset($dev['data'][$k]) && isset($dev['data'][$v])) {
							$dev['data'][$k] = $dev['data'][$v];
						}
					}
				}
			}

			$devices[] = $dev;
		}

		if (count($devices) > 0 && !isset($daemon['cli']['debug'])) {
			$data = json_encode(array('time' => $time, 'devices' => $devices));

			foreach ($collectionServer as $url) {
				$serverDataDir = $dataDir . '/' . parse_url($url, PHP_URL_HOST) . '-' . crc32($url) . '/';
				if (!file_exists($serverDataDir)) { @mkdir($serverDataDir, 0755, true); }
				if (file_exists($serverDataDir) && is_dir($dataDir)) {
					file_put_contents($serverDataDir . '/' . $time . '.js', $data);
				}
			}
		}
	}

	function unparse_url($parsed_url) {
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass = ($user || $pass) ? "$pass@" : '';
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	function submitData($data, $url) {
		global $location, $submissionKey, $probe_name;

		$url = parse_url($url);
		$thisUser = isset($url['user']) ? $url['user'] : $location;
		$thisPass = isset($url['pass']) ? $url['pass'] : $submissionKey;
		unset($url['user']);
		unset($url['pass']);
		$url = unparse_url($url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $thisUser . ':' . $thisPass);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERAGENT,'WeMo Probe: '.$probe_name);


		$result = curl_exec($ch);
		curl_close($ch);

		$result = @json_decode($result, true);
		return $result;
	}

	/**
	 * Check is a string stats with another.
	 *
	 * @param $haystack Where to look
	 * @param $needle What to look for
	 * @return True if $haystack starts with $needle
	 */
	function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}


	if (isset($daemon['cli']['search'])) { die(0); }
	if (isset($daemon['cli']['debug'])) {
		print_r($devices);
		die(0);
	}

	// Submit Data.
	foreach ($collectionServer as $url) {
		$serverDataDir = $dataDir . '/' . parse_url($url, PHP_URL_HOST) . '-' . crc32($url) . '/';

		if (file_exists($serverDataDir) && is_dir($serverDataDir)) {
			foreach (glob($serverDataDir . '/*.js') as $dataFile) {
				$data = file_get_contents($dataFile);
				$test = json_decode($data, true);
				if (isset($test['time']) && isset($test['devices'])) {
					$submitted = submitData($data, $url);
					if (isset($submitted['success'])) {
						echo 'Submitted data for: ', $test['time'], ' to ', $url, "\n";
						unlink($dataFile);
					} else {
						if (startsWith($submitted['error'], "illegal attempt to update using time")) {
							echo 'Data for ', $test['time'], ' to ', $url, ' is illegal - discarding.', "\n";
							unlink($dataFile);
						} else {
							echo 'Unable to submit data for: ', $test['time'], ' to ', $url, "\n";
							break;
						}
					}
				}
			}
		}
	}

	if (count($devices) > 0) { afterProbeAction($devices); }
