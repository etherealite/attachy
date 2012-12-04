<?php namespace Attachy;


class Attachy {


  protected $mounts;

  protected $config;

  public function __construct($config, $mount)
  {
    $this->config = $config;
    $this->mount = $mount;
  }

  public function mount($model)
  {
    return new $mount($model, $this);
  }

  public function get_mount($model)
  {
    $key = $model->getKey


  public function attach($attribute, Filer $filer, $model);
  {

  }

  public function save(Model $model, Mount $mount)
  {
    $dirty = $mount->get_dirty($model

}

