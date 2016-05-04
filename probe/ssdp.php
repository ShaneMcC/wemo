<?php

class SSDP {

	private $discoveryIPs = array('239.255.255.250');

	public function __construct($discoveryIPs = null) {
		if ($discoveryIPs !== null) {
			$this->discoveryIPs = $discoveryIPs;
		}
	}

	public function search($st = 'ssdp:all', $timeout = 2) {
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);
		foreach ($this->discoveryIPs as $sendIP) {
			$search = array();
			$search[] = 'M-SEARCH * HTTP/1.1';
			$search[] = 'Host: ' . $sendIP . ':1900';
			$search[] = 'Man: "ssdp:discover"';
			$search[] = 'ST: ' . $st;
			$search[] = 'MX: ' . $timeout;
			$search = implode($search, "\r\n") . "\r\n\r\n";

			socket_sendto($sock, $search, strlen($search), 0, $sendIP, 1900);
			socket_sendto($sock, $search, strlen($search), 0, $sendIP, 1900);
			socket_sendto($sock, $search, strlen($search), 0, $sendIP, 1900);
		}
		socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec'=>'0'));

		$result = array();
		while (true) {
			$input = null;
			$from = null;
			$port = null;
			socket_recvfrom($sock, $input, 1024, MSG_WAITALL, $from, $from);
			if ($input !== null) {
		   		foreach (explode("\r\n\r\n", trim($input)) as $reply) {
					$headers = $this->parseHeaders($reply);
					if (isset($headers['usn'])) {
						$result[$headers['usn']] = $headers;
					}
				}
			} else {
				break;
			}
		}
		socket_close($sock);

		return $result;
	}

	function parseHeaders($raw) {
		$result = array();
		foreach (explode("\n", $raw) as $header) {
			$bits = explode(':', $header, 2);
			if (isset($bits[1])) {
				$bits[0] = strtolower($bits[0]);
				if (!isset($result[$bits[0]])) { $result[$bits[0]] = array(); }
				$result[$bits[0]][] = trim($bits[1]);
			}
		}
		// Flatten any single-entry headers.
		foreach ($result as &$r) { if (count($r) == 1) { $r = $r[0]; } }
		return $result;
	}
}
