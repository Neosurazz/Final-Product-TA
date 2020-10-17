<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
  protected $guarded=[];
  public $timestamps = false;

  	public function appointments() {
        return $this->hasMany('App\Models\Appointment', 'customer_id');
  	}
}