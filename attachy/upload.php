<?php 


class Upload {

  public $post_meta;
  public $valid;

  public $keys = array(
    "name",
    "type",
    "tmp_name",
    "error",
    "size"
  );

  public $blacklist = array(
    "php",
    "js",
    "html"
  );


  public function __construct($post_meta = null)
  {
    if ( ! is_null($post_meta)) $this->post_meta;
  }


  public function is_valid()
  {
    if ( is_null ($this->valid))
    {
      return $this->validate($this->post_meta);
    }
    return $this->valid;
  }


  public function validate($post_meta = null)
  {
    if ( is_null($post_meta))
    {
      $post_meta = $this->post_meta;
    }

    $valid = $this->check($post_meta);
    $this->valid = $valid;
    return $valid;
  }


  public function augment($post_meta)
  {
    $extension = pathinfo($post_meta['tmp_name'], PATHINFO_EXTENSION);
    $post_meta['extension'] = $extension;
  }


  public function check($post_meta)
  {
    if ( ! is_array($input))
    {
      return false;
    }

    $keys = $this->keys;
    foreach(array_keys($input) as $key)
    {
      if ( ! in_array($key, $match_keys))
      {
        return false;
      }
    }
    if ( ! is_uploaded_file($input['tmp_name']))
    {
      return false;
    }

    return true;
  }


  public function __get($key)
  {
    if (array_key_exists($key, $this->post_meta))
    {
      return $this->post_meta[$key];
    }
    if ($key === "tempfile")
    {
      return $this->post_meta['tmp_name'];
    }
  }

}
