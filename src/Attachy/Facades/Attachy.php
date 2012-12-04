<?php namespace Attachy\Facades;

use Illuminate\Support\Facade;

class Attachy extends Facade {

  /**
  * Get the registered component.
  *
  * @return string
  */
  protected static function getFacadeAccessor(){ return 'attachy'; }

}
