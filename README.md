# Overview

A simple OCR service over REST. Image files to be OCRed are PUT to the server using requests similar to (using curl as an example HTTP agent):

```
curl -X PUT --data-binary @/path/to/image/file.jpg http://serverhost.example.com/ocr_rest/page/file.jpg
``

Note that the image filename is appended to the end of the request URL. This request will return the OCRed text (with a Content-Type response header of 'text/plain;charset=utf-8') to the client.

# Prerequisites

* PHP >= 5.3.0
* Tesseract (http://code.google.com/p/tesseract-ocr/)

# Installation

This server is written in the Slim PHP microframework (http://www.slimframework.com/). To install the server application, you need to clone the git repo, then install Slim (following the instructions below, using composer).

Clone the github repo beneath your Apache web root, and from within the resulting directory, issue the following commands:

1. curl -s https://getcomposer.org/installer | php
2. php composer.phar install

That's it. Your server is now ready at http://yourhost/path/to/where/you/cloned/page. For example, if you cloned the git repo directly beneath your web root, the server will be at  http://yourhost/ocr_rest/page and will accept PUT requests as illustrated above.

You should adjust settings in config.php, particularly the values in the $config section.


