# Overview and Usage

A simple OCR service over REST that uses the [Tesseract](http://code.google.com/p/tesseract-ocr/) OCR engine (although any OCR engine that has a command-line interface could be used instead). A typical workflow from a client's perspective would be to PUT the image to the server, then GET the OCRed output, either the plain text version or the [HOCR](http://en.wikipedia.org/wiki/HOCR) version. Clients can then issue a DELETE request to remove the image file from the server. The OCR operation is handed off to Tesseract during the GET requests, so these can take a few seconds to complete.

Image files to be OCRed are PUT to the server using requests similar to the following (using curl as an example HTTP agent), which include the path to the file in the request URL:

```
curl -v -X PUT --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

To retrieve a plain text representation of the file (indicated by the 'Accept: text/plain' header), issue the following GET request (note that the image filename is appended to the end of the request URL):

```
curl -v -X GET -H 'Accept: text/plain' http://host/ocr_rest/page/file.jpg
```
This request will return a response to the client with the OCRed text in the response body and a Content-Type response header of 'text/plain;charset=utf-8'. To retrieve an [HOCR](http://en.wikipedia.org/wiki/HOCR) representation of the file (indicated by the 'Accept: text/HTML' header), issue the following GET request:

```
curl -v -X GET -H 'Accept: text/html' http://host/ocr_rest/page/file.jpg
```
This request will return the OCRed text in the response body and a Content-Type response header of 'text/html;charset=utf-8'.

To delete an image file, clients can issue a DELETE request to the URL of the image resource:

```
curl -v -X DELETE http://host/ocr_rest/page/Hutchinson1794-1-0257.jpg
```

Successful deletion of the image will result in a 200 response code, unsuccessful requests will result in a 500 response code.

# Prerequisites

* PHP >= 5.3.0
* [Tesseract](http://code.google.com/p/tesseract-ocr/). Install Tesseract according to the instructions provided for your operating system.

# Installation

The REST server itself is written in the [Slim PHP microframework](http://www.slimframework.com/). To install the server application, clone the git repo, then install Slim (following the instructions below, which describe how to install Slim using composer).

Clone the github repo beneath your Apache web root, and from within the resulting directory, issue the following commands:

1. ```curl -s https://getcomposer.org/installer | php```
2. ```php composer.phar install```

That's it. Your server is now ready at http://yourhost/path/to/where/you/cloned/page. For example, if you cloned the git repo directly beneath your web root, the server will be at http://yourhost/ocr_rest/page and will be ready to accept PUT requests as illustrated above.

You should adjust settings in config.php before testing, particularly the paths and allowed image file types.

# Limiting Access to your Server

This REST server provides two ways to limit access to it: via the use of API tokens and via an IP addresss whitelist.

To use API tokens, populate the $tokens array in config.php with your tokens, and have your clients use them in the 'X-Auth-Key' HTTP request header, as in this example:

```
curl -v -X PUT -H 'X-Auth-Key: %123456789rav0' --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

The OCR server does not impose any constraints on the form of the tokens - you make them up according to whatever pattern you want.

The second method of restricting access to your server is to define a list of IP addresses that requests can come from. Do this in the $allowed_ip_addresses array in config.php. IP addresses are matched using a regular expression, so you can define basic address ranges within your whitelist.

If either array is defined, the server responds to all requests that fail the authorization with a 403 HTTP response code.

# Logging

The application logs some basic info to the web server's error log, including:

* The full command used to run the OCR engine is logged.
* If the OCR engine encouters an error (specifically, if its exit code is not 0), the output of the command is logged.
* Paths to the image file uploaded during PUT requests, and the OCR output generated during GET requests, are logged.
* During GET requests, if the expected OCR output files are not found (possibly because they were not created or there is no correspoding image file), their paths are logged.

# To do

* More, better error detection and handling.
* Add logging options.
* Add deletion of OCR output.
* Add round-robin server selection.
* Add PDF output.
