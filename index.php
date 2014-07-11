<?php

/**
 * A simple Resource-oriented server that performs OCR on a file.
 * Currently uses tesseract as the OCR engine.
 *
 * Written in the Slim micro-framework, slimframework.com.
 * 
 * Released into the Public Domain/distributed under the unlicense/CC0.
 */

require 'config.php';

// Slim setup.
require 'vendor/autoload.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();  
$app->config('log.enabled', $log_enabled);

/**
 * Slim middleware hook that fires before every request, in this case,
 * to perform authorization by token or IP address.
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
 * Example request: curl -X PUT --data-binary @/path/to/image/file.jpg http://host/ocr_rest/page/file.jpg
 *
 * @param string $filename
 *  The filename appended to /page, tokenized by :filename.
 * @param object $app
 *  The global $app object instantiated at the top of this file.
 */
$app->put('/page/:filename', function ($filename) use ($app) {
  global $paths;
  global $allowed_image_extensions;

  // Log some stuff to STDERR.
  $log = $app->getLog();

  // Check to make sure that the file's extension is in the list of
  // allowed values.
  $file_path_info = pathinfo($filename);
  if (!in_array($file_path_info['extension'], $allowed_image_extensions)) {
    $log->debug("Image file format not allowed: " . $filename);
    $app->halt(400);
  }

  // Create the subdirectory where the images and transcripts will be written.
  if (!file_exists($paths['image_base_dir'])) {
    mkdir($paths['image_base_dir'], 0777, TRUE);
  }
  if (!file_exists($paths['transcript_base_dir'])) {
    mkdir($paths['transcript_base_dir'], 0777, TRUE);
  }

  $request = $app->request();
  // Get the image content from the request body.
  $page_image_content = $request->getBody();
 
  $request = $app->request();
  $image_input_path = $paths['image_base_dir'] . $filename;

  // Write out the image file.
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
 * Route for GET /page. Example requests: curl -X GET -v -H 'Accept: text/html' http://host/ocr_rest/page/file.jpg
 * and curl -X GET -v -H 'Accept: text/plain`' http://host/ocr_rest/page/file.jpg
 *
 * @param string $filename
 *  The filename appended to /page, tokenized by :filename.
 * @param object $app
 *  The global $app object instantiated at the top of this file.
 */
$app->get('/page/:filename', function ($filename) use ($app) {
  global $paths;
  $request = $app->request();
  $image_input_path = $paths['image_base_dir'] . $filename;

  // Log some stuff to STDERR.
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
    $command = $paths['ocr_engine'] . ' ' . escapeshellarg($image_input_path) . ' ' . 
      $transcript_output_path . ' hocr';
    $log->debug("Command: " . $command);
    $time_pre = microtime(true);
    $ret = exec($command, $ret, $exit_value);
    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;
    if ($exit_value) {
      $log->debug("Exit value for $command was not 0: " . $ret);
    }
    else {
      $log->debug("Transcript creation time (seconds): " . $exec_time);
    }
    // Write out transcript to the client. Tesseract adds the extension
    // .html to its HOCR output file. 
    $transcript = file_get_contents($transcript_output_path . '.html');
    $app->response->headers->set('Content-Type', 'text/html;charset=utf-8');
    $app->response->setBody($transcript);
  }
  // If the client wants text, generate text output.
  elseif (preg_match('/text\/plain/', $request->headers('Accept'))) {
    $transcript_output_path = getTranscriptPathFromImagePath($image_input_path);
    $log->debug("Transcript output path: " . $transcript_output_path);
    // Execute OCR command
    $command = $paths['ocr_engine'] . ' ' . escapeshellarg($image_input_path) . ' ' . 
      $transcript_output_path;
    $log->debug("Command: " . $command);
    $time_pre = microtime(true);
    $ret = exec($command, $ret, $exit_value);
    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;    
    if ($exit_value) {
      $log->debug("Exit value for $command was not 0: " . $ret);
    }
    else {
      $log->debug("Transcript creation time (seconds): " . $exec_time);
    }
    // Write out transcript to the client. Tesseract adds the extension
    // .txt to its plain text output file.
    $transcript = file_get_contents($transcript_output_path . '.txt');
    $app->response->headers->set('Content-Type', 'text/plain;charset=utf-8');
    $app->response->setBody($transcript);
  }
  else {
     $log->debug("No Accept request header provided, don't know what to do.");
     $app->halt(300);
  }
});

/**
 * Route for DELETE /page. No request body is returned, but a reponse code of either 200 (on success) or
 * 500 (on failure) is returned.
 * Example request: curl -X DELETE http://host/ocr_rest/page/file.jpg
 *
 * @param string $filename
 *  The filename appended to /page, tokenized by :filename.
 * @param object $app
 *  The global $app object instantiated at the top of this file.
 */
$app->delete('/page/:filename', function ($filename) use ($app) {
  global $paths;
  $image_input_path = $paths['image_base_dir'] . $filename;

  $log = $app->getLog();

  // Check to see if the image file exists and if not, return a 204 No Content
  // response.
  if (!file_exists($image_input_path)) {
     $log->debug("Image not found in GET: " . $image_input_path);
     $app->halt(204);
  }

  if (unlink($image_input_path)) {
    $log->debug("Image DELETE succeeded: " . $image_input_path);
    // Delete the corresponding transcripts. Assumes that the image file existed and
    // was successfully deleted.
    $txt_transcript_path = getTranscriptPathFromImagePath($image_input_path) . '.txt';
    if (file_exists($txt_transcript_path)) {
      if (unlink($txt_transcript_path)) {
        $log->debug("Text transcript DELETE succeeded: " . $txt_transcript_path);
      }
    }
    $html_transcript_path = getTranscriptPathFromImagePath($image_input_path) . '.html';
    if (file_exists($html_transcript_path)) {
        $log->debug("HTML transcript DELETE succeeded: " . $html_transcript_path);
      unlink($html_transcript_path);
    }
    $app->halt(200);
  }
  else {
    $app->halt(500);
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
 *  /tmp/ocr_images/Hutchinson1794-1-0253.jpg
 *
 * @return string
 *  The full path to the transcript file corresponding to the image without an extension.
 *  For example: /tmp/docr_transcripts/Hutchinson1794-1-0253
 */
function getTranscriptPathFromImagePath($image_path) {
  global $paths;
  // Replace the image base directory configuration value with the
  // transcript base directory configuration value.
  $image_base_path_pattern = '#' . $paths['image_base_dir'] . '#';
  $tmp_path = preg_replace($image_base_path_pattern, $paths['transcript_base_dir'], $image_path);
  $path_parts = pathinfo($tmp_path);
  $transcript_path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'];
  return $transcript_path;
}

?>
