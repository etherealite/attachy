<?php namespace Attachy\Storage;

class FileSystem extends Driver {

  public function allocate($directory)
  {
    mkdir($dirname, 0770, true);
    $distination = $dirname.$guid.'.'.$extension;
    copy($source, $destination);
  }
}
