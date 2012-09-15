<?php namespace Attachy\Storage;

class Storage {

  public $strategy = "guid";
  public $repository = "filesystem";

  /*
   * generate a path based on slicing the  most repeated guid bits.
   *
   * @param  string $guid
   */
  public static function path_guid($guid)
  {
    $significant = substr($guid, -4);
    $reverse = strrev($significant);
    $path = chunk_split($reverse, 2, DS);
    return $path;
  }


  public function store_guid($file, $extension)
  {
    $guid = uniqid();
    // relative to the storage directory.
    $relative = static::path_guid($guid);
    $dirname = $dir.DS.$relative;
    $absolute = $base.DS.$relative;
    return $guid.'.'.$extension;
  }


  public function make_dir($path)
  {
    mkdir($dirname, 0770, true);
    $distination = $dirname.$guid.'.'.$extension;
    copy($source, $destination);
  }
}
