<?php

Autoloader::directories(array(
  path('app').'attachy'
));

Autoloader::namespaces(array(
  'Attachy' => __DIR__.'/src'
));

Autoloader::alias('Attachy\Filer', 'Filer');

