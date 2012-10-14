<?php

return array(
  "storage" => array(
    "strategy" => "GUID",
    "driver" => "filesystem",
    "directory" => path('public'),
  ),
  "filers" => path('app').'attachy'
);
