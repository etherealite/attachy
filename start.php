<?php

$filers = Config::get('attachy.filers.location');
Autoloader::directories(array(
  $filers,
));

Autoloader::namespaces(array(
  'Attachy' => __DIR__.'/src'
));

Autoloader::alias('Attachy\Filer', 'Filer');

