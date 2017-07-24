## Setup
```
git clone https://github.com/ukchukx/laravel_docker image-gallery 
cd image-gallery 
docker run --rm -v $(pwd):/app composer/composer install
cp .env.example .env
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan optimize
rm database/migrations/*.php
docker-compose exec app touch storage/logs/laravel.log
docker-compose exec app mkdir public/images
docker-compose exec app chown -R a+w app storage public/images
```
## DB Setup

In `.env`
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=root
DB_PASSWORD=
```
## Model
```
docker-compose exec app php artisan make:migration create_image_gallery_table
```
Replace the body of the created file in `database/migrations` with
```
    public function up() {
      Schema::create('image_gallery', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->string('image');
        $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
      Schema::drop('image_gallery');
    }
```
Create the model with:
```
docker-compose exec app php artisan make:model ImageGallery
```
Replace the body of `app/ImageGallery.php` with:
```
protected $table = 'image_gallery';
protected $fillable = ['title', 'image'];

public function delete() {
    $path = public_path('images') .'/'. $this->image;
    if(file_exists($path)) {
      @unlink($path);
    }
    
    parent::delete();
}
```

## Routes
Replace contents of `web/routes.php` with:
```
Route::get('/', 'ImageGalleryController@index');
Route::post('/', 'ImageGalleryController@upload');
Route::delete('/{id}', 'ImageGalleryController@destroy');
```

## Controller
Create a controller with:
```
docker-compose exec app php artisan make:controller ImageGalleryController
```
Replace the contents of the file (`app/Http/Controllers/ImageGalleryController.php`) with:
```
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ImageGallery;

class ImageGalleryController extends Controller {
  /**
  * Listing Of images gallery
  *
  * @return \Illuminate\Http\Response
  */
  public function index() {
    $images = ImageGallery::get();
    return view('image-gallery', compact('images'));
  }
  /**
  * Upload image function
  *
  * @return \Illuminate\Http\Response
  */
  public function upload(Request $request) {
    $this->validate($request, [
      'title' => 'required',
      'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

      $input['image'] = time().'.'.$request->image->getClientOriginalExtension();
      $request->image->move(public_path('images'), $input['image']);

      $input['title'] = $request->title;
      ImageGallery::create($input);

    return back()->with('success','Image Uploaded successfully.');
  }

  /**
  * Remove Image function
  *
  * @return \Illuminate\Http\Response
  */
  public function destroy($id) {
    ImageGallery::find($id)->delete();
    return back()->with('success','Image removed successfully.');	
  }
}
```

## View
Create a file `resources/views/image-gallery.blade.php` and paste this:
```
<!DOCTYPE html>
<html>
  <head>
    <title>Image Gallery Example</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- References: https://github.com/fancyapps/fancyBox -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>

    <style type="text/css">
      .gallery
      {
          display: inline-block;
          margin-top: 20px;
      }
      .close-icon{
        border-radius: 50%;
          position: absolute;
          right: 5px;
          top: -10px;
          padding: 5px 8px;
      }
      .form-image-upload{
          background: #e8e8e8 none repeat scroll 0 0;
          padding: 15px;
      }
    </style>
  </head>
  <body>

  <div class="container">

    <h3>Laravel - Image Gallery CRUD Example</h3>
    <form action="{{ url('/') }}" class="form-image-upload" method="POST" enctype="multipart/form-data">

      {!! csrf_field() !!}

      @if (count($errors) > 0)
        <div class="alert alert-danger">
          <strong>Whoops!</strong> There were some problems with your input.<br><br>
          <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if ($message = Session::get('success'))
      <div class="alert alert-success alert-block">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
      </div>
      @endif

      <div class="row">
        <div class="col-md-5">
          <strong>Title:</strong>
          <input type="text" name="title" class="form-control" placeholder="Title">
        </div>
        <div class="col-md-5">
          <strong>Image:</strong>
          <input type="file" name="image" class="form-control">
        </div>
        <div class="col-md-2">
          <br/>
          <button type="submit" class="btn btn-success">Upload</button>
        </div>
      </div>

    </form> 

    <div class="row">
      <div class='list-group gallery'>

        @if($images->count())
          @foreach($images as $image)
          <div class='col-sm-4 col-xs-6 col-md-3 col-lg-3'>
            <a class="thumbnail fancybox" rel="ligthbox" href="/images/{{ $image->image }}">
              <img class="img-responsive" alt="" src="/images/{{ $image->image }}" />
              <div class='text-center'>
                <small class='text-muted'>{{ $image->title }}</small>
              </div> <!-- text-center / end -->
            </a>
            <form action="{{ url('/',$image->id) }}" method="POST">
              <input type="hidden" name="_method" value="delete">
              {!! csrf_field() !!}
              <button type="submit" class="close-icon btn btn-danger"><i class="glyphicon glyphicon-remove"></i></button>
            </form>
          </div> <!-- col-6 / end -->
          @endforeach
        @endif
      </div> <!-- list-group / end -->
    </div> <!-- row / end -->
  </div> <!-- container / end -->

  </body>
  <script type="text/javascript">
    $(document).ready(function(){
      $(".fancybox").fancybox({
        openEffect: "none",
        closeEffect: "none"
      });
    });
  </script>
</html>
```
