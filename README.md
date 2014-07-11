# Overview

A simple OCR service over REST that uses the [Tesseract](http://code.google.com/p/tesseract-ocr/) OCR engine (although any OCR engine that has a command-line interface could be used instead). A typical workflow from a client's perspective would be to PUT the image to the server, then GET the OCRed output, either the plain text version or the [HOCR](http://en.wikipedia.org/wiki/HOCR) version. Clients can then issue a DELETE request to remove the image file from the server. The OCR operation is handed off to Tesseract during the GET requests, so these can take a few seconds to complete.

# Usage Details

Image files to be OCRed are PUT to the server using requests similar to the following (using curl as an example HTTP agent). Note that you should include the name of the file being uploaded at the end of th request URL:

```
curl -X PUT --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

To retrieve a plain text representation of the file, issue the following GET request. The name of the file that was PUT is appended to the end of the request URL, and the request must include an 'Accept: text/plain' header:

```
curl -X GET -H 'Accept: text/plain' http://host/ocr_rest/page/file.jpg
```
That request will return a response to the client with the OCRed text in the response body and a Content-Type response header of 'text/plain;charset=utf-8'. To retrieve an [HOCR](http://en.wikipedia.org/wiki/HOCR) representation of the file, issue the following GET request, this time with an 'Accept: text/HTML' header:

```
curl -X GET -H 'Accept: text/html' http://host/ocr_rest/page/file.jpg
```
That request will return the OCRed text in the response body and a Content-Type response header of 'text/html;charset=utf-8'.

To delete an image file and all transcripts created from it, issue the following DELETE request (again using the filename to identify the resource):

```
curl -X DELETE http://host/ocr_rest/page/Hutchinson1794-1-0257.jpg
```

Successful deletion of the image will result in a 200 response code, unsuccessful requests will result in a 500 response code.

# Prerequisites

* PHP >= 5.3.0 (see caveat below about choice of web server)
* [Tesseract](http://code.google.com/p/tesseract-ocr/). Tesseract should be installed according to the instructions provided for your operating system before you start issuing requests to your OCR server.

# Installation

The REST server itself is written in the [Slim PHP microframework](http://www.slimframework.com/). To install the server application, clone the git repo, then install Slim following the instructions below, which describe how to install the framework using composer.

Clone the github repo beneath your Apache web root, and from within the resulting directory, issue the following commands to install Slim:

1. ```curl -s https://getcomposer.org/installer | php```
2. ```php composer.phar install```

That's it. Your server is now ready at http://yourhost/path/to/where/you/cloned/page. For example, if you cloned the git repo directly beneath your web root, the server will be at http://yourhost/ocr_rest/page and will be ready to accept PUT requests as illustrated above.

You should adjust settings in config.php before testing, particularly the paths and allowed image file types.

One caveat: Slim applications work best with Apache HTTPD web server. However, getting Slim to work with other web servers [is possible](https://github.com/codeguy/Slim).

# Limiting Access to your OCR Server

This REST server provides two ways to limit access to it: via the use of API tokens and via an IP addresss whitelist.

To use API tokens, populate the $tokens array in config.php with your tokens, and have your clients use one of them in the 'X-Auth-Key' HTTP request header, as in this example:

```
curl -v -X PUT -H 'X-Auth-Key: %123456789rav0' --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

The OCR server does not impose any constraints on the form of the tokens - you make them up according to whatever pattern you want.

The second method of restricting access to your server is to define a list of IP addresses that requests can come from. Do this in the $allowed_ip_addresses array in config.php. IP addresses are matched using a regular expression, so you can define basic address ranges within your whitelist.

If either array is defined, the server responds to all requests that fail the authorization with a 403 HTTP response code.

# Logging

The application logs some basic info to the web server's error log, including:

* The full command used to run the OCR engine is logged.
* The amount of time (in seconds) it takes to generate each transcript.
* If the OCR engine encouters an error (specifically, if its exit code is not 0), the output of the command is logged.
* Paths to the image file uploaded during PUT requests, and the OCR output generated during GET requests, are logged.
* During GET requests, if the expected OCR output files are not found (possibly because they were not created or there is no correspoding image file), their paths are logged.

Logging is enabled by default but can be turned of by setting the value of $log_enabled in config.php to false.

# To do

* Add more error detection and handling.
* Add PDF output.
