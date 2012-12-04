<?php namespace Attachy;

use Illuminate\Support\ServiceProvider;

define('ATTACHY_VERSION', '0.0.1');

class AttachyServiceProvider extends ServiceProvider {

  //protected $defer = true;

  public function register()
  {
    $this->registerAttachy();
  }

  protected function registerAttachy()
  {
    $this->app['attachy'] = $this->app->share(function($app)
    {
      $config = $this->$app['config']['attachy'];
      return new Attachy($config);
    });
  }

  public function provides()
  {
    return array('attachy');
  }

}

