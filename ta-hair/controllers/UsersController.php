<?php 
namespace App\Controllers;

use App\Models\User;

class UsersController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
        $users = User::get();
        $this->view($response, 'users/index.twig', compact('users'));
    }
}