<?php namespace Attachy;

use Attachy\Storage;

class Version {


  /*
   * File transformations to apply to files handled by this
   * filer object.
   *
   * @var Closure
   */
  public $transformation;


  /* Storage instance to implement file system functionality.
   *
   * @var Attachy\Storage
   */
  public $storage;

  /* 
   * The database  used to retrieve this file version
   *
   * @var string
   */
  public $file_key;


  public function __construct()
  {
    //$this->storage = IoC::resolve();

  }

  public static function get_instance()
  {

    return new static;

  }


  public function save($source, $realname, $filekey = null)
  {
    $storage = $this->storage();
    if ($filekey === null)
    $filekey = $storage->store($source, $realname);
    return $storage->path($filekey);
  }


  /*
   * retieve the path to the file.
   * 
   * @return string
   */
  public function retrieve()
  {
    $storage = $this->storage;
    return $storage->path($filekey);
  }


  /*
   * The string representation of this object.
   *
   * @return  string
   */
  public function __toString()
  {
    return $this->retrieve();
  }


  /*
   * register any transformations to perform on source file
   * before saving
   *
   * @param Closure $transforms
   *
   * @return void
   */
  public function tranfsorm(Closure $transform)
  {
    $this->transformation = $transform;
  }
}
