<?php

Autoloader::directories(array(
  path('app').'attachy'
));

Autoloader::map(array(
  'Filer' => __DIR__.'/attachy/filer.php'
));

