<?php namespace Attachy\Adaptors\ORM\Eloquent;

use Attachy\Mounts\Eloquent as Mount;
use Attachy\Filer;

trait Eloquent {


  /*
   * An instance of the mount to store Filers
   *
   * @var Attachy\Mounts\Eloquent
   */
  protected $mount;


  /*
   * Allow for registration of Filers on attributes
   *
   * @param array $attributes
   *
   * @return void
   */
  public function __construct(array $attributes = array())
  {
    call_user_func(array($this, 'mountFilers'));
  }


  /*
    * Mutator for access to $mount property
    *
    * @return Attachy\Mounts\Eloquent
    */
  private function get_mount()
  {
    if ( $this->mount == null)
    {
      $this->mount = new Mount($this);
    }
    return $this->mount;
  }


  /*
   * Template for registering attribute to mount Filers
   *
   * @return void
   */
  abstract function mountFilers();


  /*
   * Retrieve a filer by attribute $key
   *
   * @param string $key
   * 
   * @return Attachy\Filer
   */
  public function getPlainAttribute($key)
  {
    if (($filer = $this->mount->getFiler($key)) != null)
    {
      return $filer;
    }
    parent::getPlainAttribute($key);
  }


  /*
   * Pass the save message to the composed $mount isntance.
   *
   * @return void
   */
  public function save()
  {
    $this->mount->save();
    parent::save();
  }


}
