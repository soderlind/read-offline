# Resize animated Gif files


This package aims to implement a proper resizing of gif files encompassing the GIF89a specification.
 

## Introduction

Most, if not all other publicly available gif resize packages fails with optimized gif files,
those where only parts of the file are updated in subsequent frames. See these as a background image,
with sprites moving about.
The resulting gif will retain its aspect ratio.

The package is a bit pedantic in its approach. It was made as much for me to learn what Gifs were 
and how they work, as it was to solve specific problem.

## Usage

The package needs to write to a file, the reasons for not just return a string is twofold.
One being memory usage, the other is that you really don't want to be dynamically resizing
often used gif files every time they are used.

### Import
Add this requirement to your `composer.json` file:
```json
    "grandt/phpresizegif": ">=1.0.3"
```

### Composer
If you already have Composer installed, skip this part.

[Packagist](https://packagist.org/), the main composer repository has a neat and very short guide.

Or you can look at the guide at the [Composer site](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).
 
The easiest for first time users, is to have the composer installed in the same directory as your composer.json file, though there are better options.

Run this from the command line:
```
php -r "readfile('https://getcomposer.org/installer');" | php
```

This will check your PHP installation, and download the `composer.phar`, which is the composer binary. This file is not needed on the server though.

Once composer is installed you can create the `composer.json` file to import this package.
```json
{
    "require": {
        "grandt/phpresizegif": ">=1.0.3"
    }
}
```

Followed by telling Composer to install the dependencies.
```
php composer.phar install
```

this will download and place all dependencies defined in your `composer.json` file in the `vendor` directory.

Finally, you include the `autoload.php` file in the new `vendor` directory.
```php
<?php
    require 'vendor/autoload.php';
    .
    .
    .
```

### Initialization
```php
include "../vendor/autoload.php";
use grandt\ResizeGif\ResizeGif;

$srcFile = "[path to original gif file]";
$dstFile = "[path to resized file]";

ResizeGif::ResizeToWidth($srcFile, $dstFile, 100);
```

### To make a 100 pixel wide thumbnail

```php
ResizeGif::ResizeToWidth($srcFile, $dstFile, 100);
```

### To make a 100 pixel high thumbnail

```php
ResizeGif::ResizeToHeight($srcFile, $dstFile, 100);
```

### To double the size of the gif.

```php
ResizeGif::ResizeByRatio($srcFile, $dstFile, 2.0);
```

### To half the size of the gif.

```php
ResizeGif::ResizeByRatio($srcFile, $dstFile, 0.5);
```
