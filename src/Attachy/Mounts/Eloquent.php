<?php namespace Attachy\Mounts\Eloquent;

use Illuminate\Database\Model;
use Symfony\Component\HttpFoundation\FileBag;


class Eloquent {

  protected $filers;

  protected $model;

  protected $attachy;


  public function  __construct(Model $model, Attachy $attachy)
  {
    $this->$model = $model;
    $this->$attachy = $attachy;
  }


  /*
   * Configure and return a Filer instance for this model
   *
   * @param string        $attribute
   *
   * @return Attach\Filer
   */
  public function getFiler($attribute)
  {
    return $this->filers[$attribute];
  }

  /*
   * Send all attached filers the save message
   *
   * @return void
   */
  public function save()
  {
    $model = $this->model;
    if ($model->exists)
    {
      $attributes = array_keys($this->$filers);
      foreach ($attributes as $attribute)
      {
        $filer = $this->getFiler($attribute);
        $value = $model->$attribute;
      }
    }

  }

  public function mount($attribute, Filer $filer)
  {
    $key = $this->model->$attribute;
    $filer->setKey($key);
    $this->filers[$attribute] = $filer;
  }

  /*
   * Allow calling as a function
   *
   * @param string         $attribute;
   * @param Attachy\Filer  $filer
   *
   * @return void
   */
  public function __invoke($attribute, Filer $filer)
  {
    $this->mount($attribute, $filer);
  }

}
