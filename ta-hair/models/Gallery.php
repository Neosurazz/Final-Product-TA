<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model {
    protected $guarded=[];
    public $timestamps = false;

    public function getImageAttribute($value) {
        return "/images/galleries/".$value;
    }
}