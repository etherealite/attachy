<?php namespace Attachy;


class Upload {

  public $blacklist = array(
    "php",
    "js",
    "html",
    "css"
  );

  private $post_meta;

  private $valid;

  private $valid_keys = array(
    "name",
    "type",
    "tmp_name",
    "error",
    "size"
  );

  private $messages;


  public function __construct($post_meta = null)
  {
    if ( ! is_null($post_meta)) $this->post_meta = $post_meta;
  }


  public function is_valid()
  {
    $valid_keys = $this->valid_keys;
    if (empty($this->post_meta))
    {
      throw new \Exception('post_meta is not set');
    }
    elseif ( $this->valid === null)
    {
      $this->valid = $this->validate($this->post_meta, $valid_keys);
      return $this->valid;
    }
    return $this->valid;
  }


  public function validate($post_meta, $valid_keys)
  {
    if ( ! is_array($post_meta))
    {
      $this->messages[] = "no upload meta found";
      return false;
    }
    $is_valid = true;
    foreach($valid_keys as $key)
    {
      if ( ! in_array($key, array_keys($post_meta)))
      {
        $this->messages[] = "post request missing '{$key}' data.";
        $is_valid = false;
      }
    }
    if ( ! is_uploaded_file($post_meta['tmp_name']))
    {
      $this->messages[] = "Temp file: {$post_meta['tmp_name']} is not an".
       " 'uploaded file'";
      $is_valid = false;
    }
    return $is_valid;
  }


  public function __get($key)
  {
    if (array_key_exists($key, $this->post_meta))
    {
      return $this->post_meta[$key];
    }
    elseif ($key === "tempfile")
    {
      return $this->post_meta['tmp_name'];
    }
    elseif($key === "messages")
    {
      return $this->messages;
    }
  }

}
