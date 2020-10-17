<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model {
	protected $guarded=[];
	public $timestamps = false;
	protected $dates = [
        'datetime',
        'created_at',
    ];

	public function user() {
		return $this->belongsTo('App\Models\User','customer_id');
	}

	public function service() {
		return $this->belongsTo('App\Models\Service');
	}
}