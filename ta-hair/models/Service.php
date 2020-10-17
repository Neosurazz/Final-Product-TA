<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $guarded=[];
    public $timestamps = false;

    public function appointment() {
        return $this->hasOne('App\Models\Appointment');
    }

    public function getImageAttribute($value) {
        return "/images/services/".$value;
    }
}