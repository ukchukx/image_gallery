<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImageGallery extends Model {
  protected $table = 'image_gallery';
  protected $fillable = ['title', 'image'];

  public function delete() {
    $path = public_path('images') .'/'. $this->image;
    if(file_exists($path)) {
      @unlink($path);
    }
    
    parent::delete();
  }
}
