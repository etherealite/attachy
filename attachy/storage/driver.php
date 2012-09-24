<?php namespace Attachy\Storage;


abstract class Driver {

  public $characters = '/[^A-Za-z0-9_\-\.]/';
  public $extensions = '/\.(php|phtml|ph[3-6]|phpsh)$/';


  public function store($source, $filename)
  {
    $characters = $this->characters;
    $extensions = $this->extensions;
    $escaped = $this->escape($filename, $characters, $extensions); 

    return $this->deposit($source, $filename);
  }


  public function escape($filename, $characters, $extensions)
  {
    $filename = preg_replace($characters, '_', $filename);

    $filename = preg_replace($extensions, '', $filename);
    return $filename;
  }


  abstract public function set_filekey($filekey);

  abstract public function get_filekey();

  abstract public function set_basepath($path);

  abstract public function get_basepath();

  abstract public function deposit($source, $filename);

  abstract public function path($key = null);

}
