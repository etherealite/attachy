<?php 

class Filer {

  public static $form_key;
  public static $base_path;
  public static $ext;


  /**
   * make sure the upload is valid
   *
   * @param  array      $input
   * @return boolean
   */
  public static function is_upload($input)
  {
    if ( ! is_array($input))
    {
      return false;
    }

    $match_keys = array(
      "name",
      "type",
      "tmp_name",
      "error",
      "size"
    );
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


  public static function get_temp($input)
  {
    return $input['tmp_name'];
  }


  public static function store($file)
  {
    $key = uniqid();
    $self = new static;
    $store_dir = $self->store_dir;
    $ext = static::$extension;
    $destination = static::key_path($key, $store_dir, $ext);
    mkdir(dirname($destination), 1771, true);
    rename($file, $destination);
    return $key;
  }


  public static function key_path($key, $base_path, $ext = null)
  {
    $nibbles = substr($key, -4);
    $reverse = strrev($nibbles);
    $dirname = $base_path.DS.chunk_split($reverse, 2, DS);
    $absolute = $dirname.$key.'.'.$ext;
    return $absolute;
  }


  public static function listen($class = null)
  {
    if ($class === null) $class = static::$listen_class;
    event::listen("eloquent.saving: {$class}", function($model)
    {
    log::info('it fired');
    });
  }


  public function __construct($column = null, $model = null)
  {
    $this->column = $column;
    $this->model = $model;
    $this->store_dir = $this->store_dir();
  }


  public function save()
  {
    $column = $this->column;
    // $attribute not yet written to db
    $form_key = static::$form_key;
    $attribute = $this->model->$form_key;
    // todo remove this hack to work around broken
    // eloquent __unset magic method after they
    // fix it in the core.
    $attributes =& $this->model->attributes;
    unset($attributes[$form_key]);

    $file = '';
    if (static::is_upload($attribute))
    {
      $file = static::get_temp($attribute);
    }
    elseif (request::cli() and is_string($attribute))
    {
      $file = $attribute;
    }
    else
    {
      return null;
    }

    if ( !  $this->before_store($file)) return null;

    $file_key = static::store($file);
    $this->model->$column = $file_key;

    return $file_key;
  }


  // you must override this function to set the store
  // directory.
  public function store_dir() { return null; }


  // optionally overide this method to perform actions
  // on the file before the store() method is run;
  public function before_store($file) { return true; }


}
