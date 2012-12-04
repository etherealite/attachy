<?php namespace Attachy;

use Illuminate\Database\Model;

class Mount {

  protected $model;

  protected $attachy;


  public function  __construct(Model $model, Attachy $attachy)
  {
    $this->$model = $model;
    $this->$attachy = $attachy;
  }


  /*
   * Configure  and return a Filer instance for this model
   *
   * @param string        $attribute
   * @param Attachy\Filer $filer
   *
   * @return Attach\Filer
   */
  public function attach($attribute, Filer $filer)
  {
    $attachy = $this->attachy;
    return $attachy->getFiler($attribute, $filer, $model);
  }

  /*
   * Send all attached filers the save message
   *
   * @return void
   */
  public function save()
  {
    $this->attachy->save($this->$model, $this);
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
    $this->attach($attribute, $filer);
  }

}
