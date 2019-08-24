<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Ds\Map;

$data = new Map();

$data->put('foo', 1);
$data->put('bar', 2);
$data->put('baz', 3);

$data->map(function($key, $value) use($data) {;
   echo $key, " ", $value, PHP_EOL;
   $data->remove($key);
});

var_dump($data);