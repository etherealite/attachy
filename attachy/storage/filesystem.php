<?php namespace Attachy\Storage;

use Attachy\Strategy;

class FileSystem extends Driver {

  public $basepath;
  public $strategy = "Attachy\Strategy\GUID";

  public function set_basepath($path)
  {
    $this->basepath = $path;
  }


  public function get_basepath()
  {
    $basepath = $this->basepath;
    if (empty($basepath) or ! is_string($basepath))
    {
      throw new \Exception ("no base path as been set");
    }
    return $basepath;
  }

  public function deposit($source, $filename)
  {
    $strategy = $this->strategy;
    $basepath = $this->get_basepath();

    $filekey = $strategy::filekey($filename);
    $destination = $strategy::locate($basepath, $filekey);
    mkdir(dirname($destination), 0770, true);
    copy($source, $destination);
    return $filekey;
  }


  public function path($filekey = null)
  {
    $strategy = $this->strategy;
    $basepath = $this->get_basepath();

    $location = $strategy::locate($basepath, $filekey);
    return $location;
  }

}
