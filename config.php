<?php

/**
 * OCR server config file.
 */

/**
 * General application configuration settings.
 */
$config = array(
  // Paths to the directories where uploaded imgages and their derivative
  // OCR transcripts are stored. Must be writable by the web server.
  'image_base_dir' => '/tmp/ocr_images/',
  'transcript_base_dir' => '/tmp/ocr_transcripts/',
  // A |-spearated list of extensions for the page image files you want to OCR.
  'image_file_extensions' => 'jpg|tif',
  // Path to the OCR engine.
  'ocr_engine' => "/usr/bin/tesseract"
);

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
 * File extension => mime type mapping for common image formats. 
 * If you are processing formats not in this list, add them.
 */
$image_mime_types = array(
  'bmp' => 'image/x-ms-bmp',
  'gif' => 'image/gif',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'jp2' => 'image/jp2',
  'pcx' => 'image/pcx',
  'png' => 'image/png',
  'psd' => 'image/x-photoshop',
  'tif' => 'image/tiff',
  'tiff' => 'image/tiff',
);
