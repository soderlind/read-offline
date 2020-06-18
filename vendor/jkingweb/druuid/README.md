DrUUID
======
An RFC 4122 (UUID) implementation for PHP.
  
Usage
-----
DrUUID's API has been designed to be as absolutely simple to use as possible.  Generating a UUID is as simple as including the library and issuing a single function call:

```php
<?php
require_once("lib.uuid.php");
echo UUID::mint();
?>
```

Compliance
----------
DrUUID fully complies with RFC 4122, and therefore supports Version 1 (time-based), 3 (MD5-based), 4 (random) and 5 (SHA1-based) UUIDs:

```php
<?php
require_once("lib.uuid.php");
echo UUID::mint(1)."\n";
echo UUID::mint(3, "some identifier", $private_namespace)."\n";
echo UUID::mint(4)."\n";
echo UUID::mint(5, "some identifier", $private_namespace)."\n";
```

More information
----------------

DrUUID includes an extensive and exhaustive HTML manual.  A complete break-down of features and their use is available therein.
