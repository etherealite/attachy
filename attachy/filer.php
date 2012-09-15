<?php namespace Attachy\Filer;

use Attachy\Storage;
use Attachy\Upload;

class Filer {

  public static $form_key;

  /*
   * The Eloquent model instance to draw from.
   *
   * @var Eloquent
   */
  public $model;

  /*
   * The key for the aloquent attribute save filekeys.
   *
   * @var string
   */
  public $column;

  /* Uploader instance to hold and validate post meta.
   *
   * @var Upload
   */
  public $upload;



  // TODO This is cruft until I implement  some  way of registering
  // these listeners
  public static function listen($class = null)
  {
    if ($class === null) $class = static::$listen_class;
    event::listen("eloquent.saving: {$class}", function($model)
    {
    log::info('it fired');
    });
  }

  /*
   * Create a new Filer instance
   *
   * @param string     file_column
   * @param Eloquent   $model
   */
  public function __construct($attribute = null, $model = null)
  {
    // the name of the attribute where we store
    // file names.
    $this->column = $attribute;
    $this->model = $model;
  }


  public function save()
  {
    // model attribute where file post meta or file path
    // string is located.
    $attribute = $this->$form_key;
    $intercepted = $this->intercept($this->model, $attribute);
    if( empty($intercepted))
    {
      return null;
    }
    // if the request is coming from the cli you can optionaly
    // use a string to point directly to a local file.
    elseif (Request::cli() and is_string($intercepted))
    {
      $tempfile = $intercepted;
    }
    // assuming this upload is from a post request.
    else
    {
      $upload = new Upload($intercepted);
      if($upload->is_valid())
      {
        $tempfile = $upload->tempfile;
        $extension = $upload->extension;
        $this->upload = $upload;
      }
      else 
      {
        throw new Exception("upload invalid");
      }
    }

    // subclasses can optionally end processing of  the upload by
    // overloading this method.
    if ( !  $this->before_store($file)) return null;

    // get the uploaded's file extension
    $storage = $this->get_storage();
    $storage->store($tempfile, $extension);

    // store the file to the repository
    $filekey = $storage->key;
    $column = $this->column;
    $this->model->$column = $filekey;

    return $storage->path($filekey);
  }


  /*
   * retieve the path to the stored for
   * urls.
   * 
   * @return string
   */
  public function retrieve()
  {
    $storage = $this->get_storage();
    $column = $this->column;
    $filekey = $this->model->$column;
    return $storage->path($filekey);
  }


  /*
   * get file meta from model and remove it from
   * model attributes.
   *
   * @param    Eloquent  $model
   * @param    string    $attribute
   * @return   mixed
   */
  public function intercept($model, $attribute)
  {
    $key = $attribute;
    // todo remove this hack to work around broken eloquent __unset
    // magic method after they fix it in the core.
    $attributes =& $model->attributes;
    if ( ! isset($attributes[$key]))
    {
      return null;
    }
    $value = $attributes[$key];
    unset($attributes[$key]);
    return $value;
  }


  public function get_storage($key)
  {
    return IoC::resolve('attachy.storage');
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

  // you must override this function to set the store directory.
  public function directory() { return null; }


    // optionally overide this method to perform actions on the file 
    // before the store() method is run;
  public function before_store($file) { return true; }


}
