<?php 
namespace App\Controllers;

use App\Models\Appointment;

class AppointmentsController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
        $appointments = Appointment::orderBy('id','desc')->get();
        $this->view($response, 'appointments/index.twig', compact('appointments'));
    }
}