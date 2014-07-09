<?php

/**
 * A simple Resource_oriented server that performs OCR on a file. Currently
 * uses tesseract as the OCR engine.
 *
 * Written in the Slim micro-framework, slimframework.com.
 * 
 * Released into the Public Domain/distributed under the unlicense/CC0.
 */

// Get the plugin config file and provide a default value for
// $plugins if the file cannot be loaded.
require 'config.php';

// Slim setup.
require 'vendor/autoload.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

/**
 * Slim hook that fires before every request, in this case, to
 * perform authorization by token or IP address.
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->hook('slim.before', function () use ($app) {
  global $tokens;
  global $allowed_ip_addresses;
  $request = $app->request();
  // Checks to see if client API token is in registered list. If
  // $tokens is not empty, all clients must send an X-Auth-Key
  // request header containing a valid key.
  if (count($tokens)) {
    if (!in_array($request->headers('X-Auth-Key'), $tokens)) {
      $app->halt(403);
    }
  }
  // Check if client is in IP whitelist. If $allowed_ip_addresses
  // is not empty, all client IP addresses must match a regex
  // defined in that array.
  if (count($allowed_ip_addresses)) {
    foreach ($allowed_ip_addresses as $range) {
      if (!preg_match($range, $request->getIp())) {
        $app->halt(403);
      }
    }
  }
});

/**
 * Route for PUT /page. The request body will contain the image file.
 * Example request: curl -X PUT --data-binary @/path/to/image/file.jpg http://thinkpad/ocr-server/page/file.jpg
 *
 * @param string $filename
 *  The filename appended to /page, tokenized by :filename.
 * @param object $app
 *  The global $app object instantiated at the top of this file.
 */
$app->put('/page/:filename', function ($filename) use ($app) {
  global $config;

  // Create the subdirectory where the images and transcripts will be written.
  if (!file_exists($config['image_base_dir'])) {
    mkdir($config['image_base_dir'], 0777, TRUE);
  }
  if (!file_exists($config['transcript_base_dir'])) {
    mkdir($config['transcript_base_dir'], 0777, TRUE);
  }

  $request = $app->request();
  // Get the image content from the request body.
  $page_image_content = $request->getBody();
 
  $request = $app->request();
  $image_input_path = $config['image_base_dir'] . $filename;

  // Log some stuff to the apache error_log.
  $log = $app->getLog();

  // Write out the image file
  if (file_put_contents($image_input_path, $page_image_content)) {
    $log->debug("Image output path from PUT succeeded: " . $image_input_path);
    $app->halt(201);
  }
  else {
    $log->debug("Image output path from PUT failed: " . $image_input_path);
    $app->halt(500);
  }
});

/**
 * Route for GET /page. 
 *
 * @param string $filename
 *  The filename appended to /page, tokenized by :filename.
 * @param object $app
 *  The global $app object instantiated at the top of this file.
 */
$app->get('/page/:filename', function ($filename) use ($app) {
  global $config;
  $request = $app->request();
  $image_input_path = $config['image_base_dir'] . $filename;

  // Log some stuff to the apache error_log.
  $log = $app->getLog();

  // Check to see if the image file exists and if not, return a 204 No Content
  // response.
  if (!file_exists($image_input_path)) {
     $log->debug("Image not found in GET: " . $image_input_path);
     $app->halt(204);
  }
  
  // If the client wants HTML, generate hocr output.
  if (preg_match('/text\/html/', $request->headers('Accept'))) {
    $transcript_output_path = getTranscriptPathFromImagePath($image_input_path);
    $log->debug("Transcript output path: " . $transcript_output_path);
    // Execute OCR command
    $command = $config['ocr_engine'] . ' ' . escapeshellarg($image_input_path) . ' ' . 
      $transcript_output_path . ' hocr';
    $log->debug("Command: " . $command);
    // @todo: Catch error on exec().
    $ret = exec($command, $ret, $exit_value);
    // Write out transcript to the client. Tesseract adds the extension
    // .html to its HOCR output file. 
    $transcript = file_get_contents($transcript_output_path . '.html');
    $app->response->headers->set('Content-Type', 'text/html;charset=utf-8');
    $app->response->setBody($transcript);
  }
  // If the client wants text, generate text output.
  if (preg_match('/text\/plain/', $request->headers('Accept'))) {
    $transcript_output_path = getTranscriptPathFromImagePath($image_input_path);
    $log->debug("Transcript output path: " . $transcript_output_path);
    // Execute OCR command
    $command = $config['ocr_engine'] . ' ' . escapeshellarg($image_input_path) . ' ' . 
      $transcript_output_path;
    $log->debug("Command: " . $command);
    // @todo: Catch error on exec().
    $ret = exec($command, $ret, $exit_value);
    // Write out transcript to the client. Tesseract adds the extension
    // .txt to its plain text output file.
    $transcript = file_get_contents($transcript_output_path . '.txt');
    $app->response->headers->set('Content-Type', 'text/plain;charset=utf-8');
    $app->response->setBody($transcript);
  }
});

// Run the Slim app.
$app->run();

/**
 * Functions.
 */

/**
 * Creates a path to a transcript from a image path.
 *
 * @param string $image_path
 *  The full path to the image being processed. For example:
 *  /home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg
 *
 * @return string
 *  The full path to the transcript file corresponding to the image. For example:
 *  /tmp/docr_transcripts/Hutchinson1794-1-0253.txt
 */
function getTranscriptPathFromImagePath($image_path) {
  global $config;
  // Replace the image base directory configuration value with the
  // transcript base directory configuration value.
  $image_base_path_pattern = '#' . $config['image_base_dir'] . '#';
  $tmp_path = preg_replace($image_base_path_pattern, $config['transcript_base_dir'], $image_path);
  $path_parts = pathinfo($tmp_path);
  $transcript_path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'];
  return $transcript_path;
}

?>
