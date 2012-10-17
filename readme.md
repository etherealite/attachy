# Attachy - A Carrierwave(Ruby on Rails gem) like Upload handling framework for Laravel.

paragraph long plug goes here.

## Features
- PHP 5.3 compatible
- Carrierwave like versioning support.
- Clean \- only needs one added field in your database table per file.
- Dependancy injected
- Not explicity dependant on Elequent, doesn't extend Eloquent models.
- Works with mass assignment
- Modular \- Extend to use S3 for storage, handle ajax uploads.
- Less magical lets you decide how to make your thumbnails and design your databse tables.
- More secure - Filters both dangerous filenames and file extensions like '.php'.

## Usage with Eloquent

Insert a new string column into your table to hold file meta for Attachy.

```php
<?php

Schema::create('posts', function($table)
{
  $table->increments('id');
  $table->string('title');
  $table->string('cool_pic');
```

Go to your application directory and create a new subdirectory called 'attachy'

```bash
cd /project/application
mkdir attachy
cd attachy/
```

Inside the application/attachy directory that you just created,
make a new php source file called 'coolpic.php'.

```php
<?php
class CoolPic extends Filer {}
```


Now to make the model class for our posts.

```php
<?php

class Post extends Eloquent {

  public function get_cool_pic()
  {
    // Tell Attachy to save the file identifer to the 
    cool_pic column defined in the previously created  migration.
    return CoolPic::attach("cool_pic", $this);
  }

  // Overload the Elquent save method.
  public function save()
  {
    $this->cool_pick->save();
    parent::save();
  }
}
```
In the 'attach' method, the first parameter denotes the name of the attribute
in the previously created migration, upon which, Attachy will store the 
information required to retrieve the file path later on.

A simple form for our Post is as follows:

```php
{{ Form::open_for_files('post') }}
  {{ Form::file('cool_pic') }}
  {{ Form::submit('Post') }}
{{ Form::close() }}
```

Attachy by convention will look for an underscored version of the class
name of your Filer class,
supplied by mass assignment. in this case 'CoolPic' in your post fields

Then, in our route.php we can use mass assignment to map the
post fields directly to our model


```php
<?php

Route::post('new_post', function()
{
  // populating and saving your model is not changed when using
  // Attachy.
  $post = new Post(Input::all());
  $post->save();
}
```

Thats it, you don't have to worry about checking for multipart request
forgery or harmfull file extensions such as .php or .sh, it's handled
for you already.


To Generate an image tag.
```php
{{ HTML::image($post->cool_pic) }}
```

In this example form, the name for the form input field is 

In order to generate a thumbnail version of our image we can create
a new version of the file that has been resized. Return to our CoolPic
class that we previously defined in application/attachy/coolpic.php 
and change to match the following code.

```php
class CoolPic extends Filer {

  static function has_versions()
  {
    static::register("thumbnail", function($version)
    {
      $version->transform(function($storage)
      {
        $path = $storage->retrieve();
        // do not change the file name or location set by Attachy
        $handle = fopen($path, 'r+');
        $thumb = new Imagick();
        $thumb->readImageFile($handle);
        $thumb->thumbnailImage("250", "250");
        $thumb->writeImage($handle);
      });
    });
  }
}
```

The first perameter of the 'register' method is the name to 
which the version will be refered by when retrieving its file path
. The second parameter is a lambda which will
be used to configure the version. There are many configuration options
but right now we'll cover only cover the transform method. Using the
transform method on the newly created version we can register another 
lambda that will be used to manipulate the image file.

To generate a link to the thumbnail Version of the file:

```php
{{ HTML::image($post->cool_pic('thumbnail')) }}
```

Using this design, Attachy allows you to perform any work you please to
process your files, without forcing you to use a restrictive API. This
frees you to make your own decisions on whether to use Image Magick,
Gmagick, GD. You can even do non image related processing such as make
a zip file of file contents to publish, the possibilties are limited only
to what is possible to wright to a file.
