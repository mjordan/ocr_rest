# Overview

A simple OCR service over REST. Image files to be OCRed are PUT to the server using requests similar to (using curl as an example HTTP agent):

```
curl -X PUT --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

Note that the image filename is appended to the end of the request URL. This request will return the OCRed text (with a Content-Type response header of 'text/plain;charset=utf-8') to the client.

# Prerequisites

* PHP >= 5.3.0
* Tesseract (http://code.google.com/p/tesseract-ocr/)

# Installation

This server is written in the Slim PHP microframework (http://www.slimframework.com/). To install the server application, you need to clone the git repo, then install Slim (following the instructions below show you how to do this using composer).

Clone the github repo beneath your Apache web root, and from within the resulting directory, issue the following commands:

1. ```curl -s https://getcomposer.org/installer | php```
2. ```php composer.phar install```

That's it. Your server is now ready at http://yourhost/path/to/where/you/cloned/page. For example, if you cloned the git repo directly beneath your web root, the server will be at http://yourhost/ocr_rest/page and will accept PUT requests as illustrated above.

You should adjust settings in config.php, particularly the values in the $config section.

# Limiting Access to your Server

This OCR server provides two ways to limit access to it: via the use of API tokens and via an IP addresss whitelist.

To use API tokens, populate the $tokens array in config.php with your tokens, and have your clients use them in the 'X-Auth-Key' HTTP request header, as in this example:

```
curl -X PUT -H 'X-Auth-Key: %123456789rav0' --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
```

The OCR server does not impose any constraints on the form of the tokens - you make them up according to whatever pattern you want.

The second method of restricting access to your server is to define a list of IP addresses that requests can come from. Do this in the $allowed_ip_addresses array in config.php. IP addresses are matched using a regular expression, so you can define basic address ranges within your whitelist.

If either array is defined, the server responds to all requests that fail the authorization with a 403 HTTP response code.

