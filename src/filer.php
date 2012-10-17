<?php namespace Attachy;

use Attachy\Storage;
use Laravel\Request;
use Laravel\Database\Eloquent\Model as Eloquent;

abstract class Filer {


  /* auto save all registered versions
   *
   * @var boolean
   */
  public static $auto_save = true;


  /*
   * The key for the eloquent $attribute property that we're
   * going to intercept.
   *
   * @var string
   */
  public static $form_key;


  /*
   * object pool for caching versions
   *
   * @todo emplement methods to actually use this.
   *
   * @var array
   */
  public static $versions = array();


  /*
   * closure used in making versions
   *
   * @var array
   */
  public static $registered = array();


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

  /* Storage instance to implement file system functionality.
   *
   * @var Attachy\Storage
   */
  private $storage;

  /*
   * The version of this filer instance
   *
   * @var string
   */
  public $version;


  /*
   * File transformations to apply to files handled by this
   * filer object.
   *
   * @var Closure
   */
  public $transformation;


  /*
   * Return a new filer instance visiting a client model.
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
    $class = get_called_class();
    $default = new static;

    if (static::$form_key === null)
    {
      static::$form_key = static::to_underscore($class);
    }

    if (method_exists($class, 'has_versions'))
    {
      call_user_func(array($class, 'has_versions'));
    }

    if ( ! static::$builders["default"]);
    {
      $static::register("default", function($version)
      {
        $path = Config::get('attachy.storage.basepath');
        $path .= static::to_underscore(get_called_class());
        $version->storage->set_basepath($path);
        $version->storage();
      });
    }

    // name of the column to save filekey
    $filer->column = $column;
    // Eloquent model instance to save filekey
    $filer->model = $model;
    return $filer;
  }


  /*
   * Save a lazy loaded version from version builders to storage
   *
   * @param array  $versions
   *
   */
  public function save_versions($versions)
  {
    // allow the function take both arrays and strings as arguments.
    if ( ! is_array($versions)) $versions = array($versions);

    foreach ($versions as $version)
    {
      if ( ! in_array($version, array_keys(static::$versions)))
      {
        static::build_version($version);
      }
      $filer = static::$versions[$version];
      $path = $this->retrieve();
      $filer->store($path);
    }

  }


  /* 
   * Build a new Filer instance based on the instructions provided
   * by a registered version building closure
   *
   * @param array $versions
   *
   * @return void
   */
  public static function build_versions($versions)
  {
    // allow the function to take a single string to build the version.
    if ( ! is_array($versions)) $versions = array($versions);

    foreach ($versions as $version)
    {
      // A closure to build the version must first be registered.
      if ( ! in_array($version, array_keys(static::$registered)))
      {
        throw new Exception("version not registered");
      }
      $builder = static::$registered[$version];

      $filer = new static;
      $filer->set_version($version);
      $path = $this->storage->get_basepath();
      $version_path = $filer->storage->path_join($version_path, $version);
      $filer->storage->set_basepath($version_path);
      $builder($filer);
      Ioc::instance($version
      static::$versions[$version] = $filer;
    }
  }

  public static function version($key)
  {
    IoC::resolve($key)

  }

  public static function register($key, Closure $builder)
  {
    static::$builders[$key] = $builder;
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


  /*
   * Return the underscored version of a camelcased string
   *
   * @ param string    $class
   * 
   * @return string
   */
  public static function to_underscore($class)
  {
    $s1 = preg_replace('/(.)([A-Z][a-z]+)/', '$1_$2', $class);
    return  strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $s1));
  }


  /*
   * Capture the input from a visitee Eloquent model instance
   * and store the files described.
   */
  public function save()
  {
    // model attribute where file post meta or file path
    // string is located.
    $attribute = $this->form_key;

    if ($this->before_intercept() === false) return null;
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
      $upload = $this->validate($intercepted);
      $tempfile = $upload->tempfile;
      $realname = $upload->name;
    }
    $storage = $this->storage;
    // subclasses can optionally end processing of the upload by
    // overloading this method and returning false.
    if ( $this->before_store($storage, $upload) === false)
    {
      return null;
    }

    // set the value of the mounted model column to the filekey
    // given to us after storing the file.
    $this->update_model($filekey);
    if ($this->auto_versions)
    {
      $this->save_verions();
    }
    else
    {
      $this->save_versions('default');
    }

    // store the file to the repository
    $this->after_store($storage, $filekey);

  }


  /*
   * Store the 'filekey' of the file handled by an instance to 
   * the Eloquent model
   *
   * @param string $filekey
   *
   * @return void
   */
  public function update_model($filekey)
  {
    $column = $this->column;
    $this->model->$column = $filekey;

  }


  public function store($tempfile, $realname, $filekey = null)
  {
    $storage = $this->storage();
    if ($filekey === null)
    $filekey = $storage->store($tempfile, $realname);
    return $storage->path($filekey);
  }


  /*
   * validate the file contents of a post file upload
   * array in the same structure as the $_FILES superglobal.
   *
   * @todo need to interface to the Laravel\Messages class
   * @param array filesource
   *
   * @return Attachy\Upload
   */
  public function validate(array $intercepted)
  {
    $upload = new Upload($intercepted);
    if ($this->before_validate($upload) === false) return null;
    if( ! $upload->is_valid())
    {
      // TODO need to interface to the Laravel\Messages class
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
    $storage = $this->storage;
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
   * Load, create or cache a storage instance as necessary
   *
   * @param string   $version
   *
   * @return Attachy\Storage\Filesystem
   */
  public function storage($instance = null)
  {
    $version = $this->version;
    $key = get_called_class().'.'.'storage: '.$version;

    if ($instance !== null)
    {
      IoC::instance($key, $instance);
    }
    elseif ( ! IoC::registered($key))
    {
      $storage = new Attachy\Storage\FileSystem;
      $storage->set_basepath
        (
        );
      IoC::instance($key, $storage);
    }

    return IoC::resolve($key);
  }



  /* 
   * set the version of the filer instance
   *
   * @param string $version
   *
   * @return void
   */
  public function set_version($version)
  {
    $this->version = $version;
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


  /*
   * The string representation of this object.
   *
   * @return  string
   */
  public function __toString()
  {
    return $this->retrieve();
  }

  public function __get($key)
  {
    if ($key === 'storage')
    {
      if $this->storage !== null) return $this->storage;
      else  return $this->storage();
    }

  }

  public function before_intercept() { return true; }
  public function before_validate($upload) { return true; }
    // optionally overide this method to perform actions on the file 
    // before the store() method is run;
  public function before_store($file) { return true; }

  public function after_store($storage, $filekey) { return true; }


}
