<?php

	/** This location. */
	$location = 'Home';

	/** Submission Key. */
	$submissionKey = 'SomePassword';

	/** Collection URL. */
	$collectionServer = 'http://127.0.0.1/wemo/submit.php';

	/** Data storage directory. */
	$dataDir = dirname(__FILE__) . '/data/';

	if (file_exists(dirname(__FILE__) . '/config.user.php')) {
		require_once(dirname(__FILE__) . '/config.user.php');
	}
