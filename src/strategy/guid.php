<?php namespace Attachy\Strategy;

class GUID {

  /*
   * return guid portion of a file key.
   * <code>
   *   GUID::from_key("2s8fsa9.png");
   *   //returns string '2s8fsa9'
   * </code>
   */
  public static function from($filekey)
  {
    $guid = pathinfo($filekey, PATHINFO_FILENAME);
    return $guid;
  }


  /*
   * generate a path based on slicing the  most repeated guid bits.
   *
   * @param string    $guid
   * @param string    $ds
   */
  public static function locate($basepath, $filekey, $ds = DS)
  {
    $guid = static::from($filekey);
    $significant = substr($guid, -2);
    $reverse = strrev($significant);
    //uncomment the bottom if we're splitting on more than one nibble.
    //$path = chunk_split($reverse, 2, $ds);
    return $basepath.DS.$reverse.DS.$filekey;
  }



  /*
   * generate a file key from a filename
   */
  public static function filekey($filename)
  {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    return uniqid().'.'.$extension;
  }

}
