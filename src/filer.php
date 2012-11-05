<?php namespace Attachy;

use Attachy\Storage;
use Laravel\Request;
use Laravel\Database\Eloquent\Model as Eloquent;

abstract class Filer {


  /* auto save all registered versions
   *
   * @var boolean
   */
  private static $auto_save = true;


  /*
   * The key for the eloquent $attribute property that we're
   * going to intercept.
   *
   * @var string
   */
  private static $form_key;


  /*
   * object pool for caching versions
   *
   * @todo emplement methods to actually use this.
   *
   * @var array
   */
  public static $versions = array();


  /*
   * closures used in making versions
   *
   * @var array
   */
  private static $registered = array();

  /*
   * The Eloquent model instance to draw from.
   *
   * @var Eloquent
   */
  private $model;


  /*
   * The name of the eloquent attribute save filekeys on.
   *
   * @var string
   */
  private $column;


  /* Uploader instance to hold and validate post meta.
   *
   * @var Attachy\Upload
   */
  public $upload;


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

    // try and use a preconfigured instance
    $filer = static::get_instance($class);
    if ($filer === null)
    {
      $filer = new static;
      $filer->configure();
    }

    // configure a new instance
    // Eloquent model instance used to save filekey
    $filer->column = $column;
    $filer->model = $model;
    return $filer;
  }


  /* 
   * configure a new Filer instance
   */
  public function configure($params)
  {

    $class = static::to_underscore(get_called_class());

    if ($this->$form_key === null)
    {
      // default form_key is the underscored name of the
      // client class by convention.
      $this->$form_key = $class;
    }

    // read in any registered builders defined in the client class
    if (method_exists($this, 'has_versions'))
    {
      $this->has_versions();
    }

    // check if a user registered their own builder for default version.
    if ( ! static::$builders["default"]);
    {
      // use our own builder for the default version.
      $this->register("default", function($version)
      {
        // default behavior is a sub-dir of the base path
        // named by the underscored class name
        $base_path = Config::get('attachy.storage.basepath');
        $path = $version->storage->path_join($base_path, $class);
        $version->storage->set_basepath($path);
      });
    }
  }


  /*
   * Retrieve a preconfigured Filer instance
   *
   * @param string   $class
   *
   * @return Attachy\Filer
   */
  public function get_instance($class = null)
  {
    if ($class === null) $class = get_called_class();
    $key = "Attachy: $class";
    if ( ! IoC::registered($key)) return null;
    else return IoC::resolve($key);
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
      $all = array_keys(static::$registered);
      $this->save_verions($all);
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
   * Save a lazy loaded version from version builders to storage
   *
   * @param array  $versions
   *
   */
  public function save_versions($versions)
  {
    // allow the function take both arrays and strings as arguments.
    if ( is_string($versions)) $versions = array($versions);

    $missing = array_diff($versions, array_keys(static::$versions));
    static::build($missing);

    foreach ($versions as $version)
    {
      $version = static::$versions[$version];
      $path = $version->retrieve();
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
  public static function build($versions)
  {
    // allow the function to take a single string to build the version.
    if ( ! is_array($versions)) $versions = array($versions);

    foreach ($versions as $name)
    {
      // A closure to build the version must first be registered.
      if ( ! in_array($version, array_keys(static::$registered)))
      {
        throw new Exception("version not registered");
      }
      $builder = static::$registered[$name];

      $version = Version::get_instance();
      $storage = $this->version('default')->storage;

      $path = $storage->get_basepath();
      $version->storage->set_basepath($storage->path_join($path, $name));
      $builder($version);
      static::$versions[$name] = $version;
    }
  }


  /*
   * Return a version instance when called as a function.
   *
   *@param   string  $version
   *@return  Attachy\Version
   */
  public function __invoke($version = 'default')
  {
    return $this->version($version);
  }


  /*
   * The string representation of this object.
   *
   * @return  string
   */
  public function __toString()
  {
    return $this->version('default')->retrieve();
  }


  /*
   * Handle dynamic method calls
   *
   * @param string   $version
   * @return mixed
   */
  public function __get($version)
  {

  }

  public function before_intercept() { return true; }
  public function before_validate($upload) { return true; }
    // optionally overide this method to perform actions on the file 
    // before the store() method is run;
  public function before_store($file) { return true; }

  public function after_store($storage, $filekey) { return true; }


}
