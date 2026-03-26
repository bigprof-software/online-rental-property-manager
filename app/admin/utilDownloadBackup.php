<?php
	/**
	 * Utility script to download backup files
	 * Validates backup file existence and streams it to the user
	 * Uses passthrough to avoid revealing direct download links
	 */

	require(__DIR__ . '/incCommon.php');

	// Validate CSRF token
	if(!csrf_token(true)) {
		@header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
		exit;
	}

	// Get and validate md5_hash parameter
	$md5_hash = Request::val('md5_hash');
	if(!preg_match('/^[a-f0-9]{17,32}$/i', $md5_hash)) {
		@header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
		exit;
	}

	// Check if backup file exists
	$backup_file = __DIR__ . "/backups/{$md5_hash}.sql";
	if(!is_file($backup_file)) {
		@header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
		exit;
	}

	// Get file info
	$file_size = filesize($backup_file);
	$file_time = filemtime($backup_file);

	// Create a friendly filename with timestamp
	$filename = 'backup_' . date('Y-m-d_H-i-s', $file_time) . '.sql';

	// Set headers for file download
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . $file_size);

	// Clear output buffer
	ob_clean();
	flush();

	// Stream the file to the user
	readfile($backup_file);

	exit;
