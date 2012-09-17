<?php namespace Attachy\Storage;

class Driver {

  public $base;
  public $regex = '/[^A-Za-z0-9_\-]/';
  public $strategy;

  public function store($source, $filename = null)
  {
    if ($filename === null) $filename = basename($source);

    $strategy = $this->strategy;

    $escaped = $this->escape($filename, $this->regex);
    $key = $strategy::key($escaped);
    $directory = $strategy::directory($key);
    $this->allocate($directory);
    $this->copy($source, $directory);
  }


  public function path($key = null)
  {
    if ($key === null) $key = $this->key;
    return $this->file_path;
  }


  public function escape($filename, $regex)
  {
    return preg_replace($regex, '_', $filename);
  }



  public function key()
  {
    return this->$key
  }
}
