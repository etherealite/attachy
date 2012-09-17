<?php


class GUID {
  public static $ds = DS;

  /*
   * generate a path based on slicing the  most repeated guid bits.
   *
   * @param  string $guid
   */
  public static function directory($key, $storage)
  {
    $ds = $this->ds;
    $significant = substr($guid, -4);
    $reverse = strrev($significant);
    $path = chunk_split($reverse, 2, $ds);
    return $path;
  }

  public static function key($filename)
  {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    return uniqid().'.'.$extension;
  }

  public function store_guid($file, $extension)
  {
    $guid = 
    // relative to the storage directory.
    $dirname = $dir.DS.$relative;
    $absolute = $base.DS.$relative;
    return $guid.'.'.$extension;
  }
}
