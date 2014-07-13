<?php

/**
 * OCR server config file.
 */

/**
 * General application configuration settings.
 */
$paths = array(
  // Paths to the directories where uploaded imgages and their derivative
  // OCR transcripts are stored. Must end in / and be writable by the web
  // server.
  'image_base_dir' => '/tmp/ocr_images/',
  'transcript_base_dir' => '/tmp/ocr_transcripts/',
  // Path to the OCR engine.
  'ocr_engine' => "/usr/bin/tesseract"
);

/**
 * Boolean to turn on or off basic debug logging (which is written to STDERR,
 * in most cases your web server's error log). You may want to set this to
 * false during production.
 */
$log_enabled = true;

/**
 * Maximum time for the REST server to execute, in seconds. Performing OCR can
 * take some time, and this setting overrides the 'max_execution_time'
 * directive in your web server's php.ini for this script only.
 */
$max_exection_time = 240;

/**
 * List of token strings that authorize clients to access this ocr server.
 * Leave empty if you don't want to restrict access. Clients must send the
 * X-Auth-Key request header containing a key from this list. Tokens can be
 * as long or short as you want - it's up to you.
 */
$tokens = array(
  // 'b692d302-3435', '484d-9c1f-e4e34a4a8f92'
);

/**
 * List of regexes matching client IP addresses allowed to access this ocr
 * server. Leave empty if you don't want to restrict access by IP address.
 */
$allowed_ip_addresses = array(
  // '/^123\.243\.(\d+)\.(\d+)/', // For range 123.243.0.0 - 123.243.255.255.
);

/**
 * List of file extensions on files that are allowed to be PUT to this OCR server.
 * If you are processing files not represented in this list, add their extensions.
 */
$allowed_image_extensions = array(
  'jpg', 'jpeg', 'jp2', 'tif', 'tiff'
);
