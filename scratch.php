<?php declare(strict_types=1);

use Ds\Map;

require_once __DIR__ . '/vendor/autoload.php';

$data = new Map();

$data->put('foo', 1);
$data->put('bar', 2);
$data->put('baz', 3);

foreach ($data as $key => $value) {
    echo $key, " ", $value, PHP_EOL;
    $data->remove($key);
}

var_dump($data);

# expected output:
# foo 1
# bar 2
# baz 3

# actual output:
# foo 1
# bar 2
