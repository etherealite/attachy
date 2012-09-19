<?php namespace Attachy;

use Attachy\Storage;
use Laravel\Request;
use Laravel\Database\Eloquent\Model as Eloquent;

abstract class Filer {

  /*
   * object pool for caching instances
   *
   * @todo emplement methods to actually use this.
   *
   * @var array
   */
  public static $instances = array();


  /*
   * The key for the eloquent $attribute property that we're
   * going to intercept.
   *
   * @var string
   */
  public $form_key;

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
   * @var Attachy\Upload
   */
  public $upload;


  /*
   * Return a new filer instance.to a client eloquent model.
   *
   * @todo register the instance so we can respond to eloquent
   * 'saving' events. Is this the flyweight pattern?
   *
   * @param string     $column
   * @param Eloquent   $model
   *
   * @return Filer
   */
  public static function attach($column, $model)
  {
      $filer =  new static($column, $model);
      $filer->column = $column;
      $filer->model = $model;
      return $filer;
  }


  /*
   * Attach a listner to an eloquent event for an instance of this
   * class.
   *
   * @todo This is cruft at the moment. Need to implement listeners
   * later on.
   */
  public static function listen($class = null)
  {
    if ($class === null) $class = static::$listen_class;
    event::listen("eloquent.saving: {$class}", function($model)
    {
    log::info('it fired');
    });
  }


  public function save()
  {
    // model attribute where file post meta or file path
    // string is located.
    $attribute = $this->form_key;
    $intercepted = $this->intercept($this->model, $attribute);
    if( empty($intercepted)) return null;
    // if the request is coming from the cli you can optionaly
    // use a string to point directly to a local file. This will
    // skip any validation in this or the before_validate() method.
    elseif (Request::cli() and is_string($intercepted))
    {
      $tempfile = $intercepted;
      $realname = basename($intercepted);
    }
    // assuming this upload is from a post request.
    else
    {
      // subclasses can optionally end processing of  the upload by
      // overloading this method and returning false.
      $upload = $this->validate($intercepted);
      $tempfile = $upload->tempfile;
      $realname = $upload->name;
    }
    $storage = $this->storage();
    // subclasses can optionally end processing of the upload by
    // overloading this method and returning false.
    if ( $this->before_store($storage, $upload) === false)
    {
      return null;
    }

    $filekey = $storage->store($tempfile, $realname);

    // store the file to the repository
    $column = $this->column;
    $this->model->$column = $filekey;
    $this->after_store($storage, $filekey);

    return $storage->path($filekey);
  }


  public function validate(array $intercepted)
  {
    $upload = new Upload($intercepted);
    if ($this->before_validate($upload) === false) return null;
    if(! $upload->is_valid())
    {
      // TODO to lazy to make an exception class ATM.
      throw new \Exception(print_r($upload->messages, true));
    }
    return $upload;
  }


  /*
   * retieve the path to the file.
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
   * get post meta from Eloquent dynamic attribute and unset it.
   *
   * @todo remove hack to unset Eloquent attributes by reference.
   *
   * @param    Eloquent  $model
   * @param    string    $attribute
   * @return   mixed
   */
  public function intercept($model, $attribute)
  {
    $key = $attribute;
    $attributes =& $model->attributes;
    if ( ! isset($attributes[$key]))
    {
      return null;
    }
    $value = $attributes[$key];
    unset($attributes[$key]);
    return $value;
  }


  /*
   * return a new storage instance
   */
  public function storage()
  {
    #return IoC::resolve('attachy.storage');
    throw new \Exception("ghetto storage factory method must be overriden");
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


  public function before_validate($upload) { return true; }
    // optionally overide this method to perform actions on the file 
    // before the store() method is run;
  public function before_store($file) { return true; }

  public function after_store($storage, $filekey) { return true; }


}
