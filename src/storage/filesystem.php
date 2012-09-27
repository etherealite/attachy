<?php namespace Attachy\Storage;

use Attachy\Strategy;

class FileSystem extends Driver {

  protected $filekey;
  protected  $basepath;
  public $strategy = "Attachy\Strategy\GUID";

  public function set_filekey($filekey)
  {
    $this->filekey = $filekey;
  }

  public function get_filekey()
  {
    return $this->filekey;
  }

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

    $filekey = $this->filekey;
    if ($filekey === null)
    {
      $filekey = $strategy::filekey($filename);
      $this->set_filekey($filekey);
    }
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


  /*
   * Join an absolute directory path with a relative path
   * or basename.
   *
   * @param string   $absolute
   * @param string   $relative
   *
   * @return string
   */
  public function path_join($absolute, $relative)
  {
    return $absolute.DS.$relative;
  }

}
