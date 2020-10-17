<?php

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Staff;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

include_once('api.php');

$app->get('/login', function($request, $response, $args) {
	 return $this->view->render($response, 'login.twig');
})->setName('get-login');

$app->get('/logout', function($request, $response, $args) {
	$_SESSION['userId'] = false;
	unset($_SESSION['userId']);
	session_destroy();
	return $response->withRedirect('/login');
})->setName('get-logout');

$app->post('/login', function($request, $response, $args) {
	$postData = $request->getParsedBody();
	$email = $postData['email'];
	$password = $postData['password'];

	$staff = Staff::where([
		'email' => $email
	])->first();

	if (!empty($staff)) {
		if (password_verify($password, $staff->password)) {
			$_SESSION['userId'] = $staff->id;
			$_SESSION['isAdmin'] = $staff->is_admin;
			return $response->withRedirect('/dashboard');
		}
	}
	return $response->withRedirect('/login');
})->setName('post-login');

$app->get('/', \App\Controllers\HomeController::class . ':index')->setName('base-url');
$app->get('/payment', \App\Controllers\HomeController::class . ':payment')->setName('payment-url');
$app->get('/dashboard', \App\Controllers\HomeController::class . ':index');

$app->get('/users', \App\Controllers\UsersController::class . ':index');
$app->get('/users/message/{user_id}', \App\Controllers\UsersController::class . ':message');
$app->post('/users/message/{user_id}', \App\Controllers\UsersController::class . ':save');
$app->get('/users/getmessages/{user_id}', function($request, $response, $args) {
	$messages = \App\Models\Message::where('user_id', $args['user_id'])->orderBy('created_at', 'DESC')->get();
	$result = "";
	foreach ($messages as $key => $message) {
		$result .= '<div class="well">';
	    $result .=  $message->from. ' [' . $message->date . ']: ' . $message->text;
	    $result .= '</div>';
	}
	echo $result;
});

$app->get('/appointments', \App\Controllers\AppointmentsController::class . ':index');

$app->get('/services', \App\Controllers\ServiceController::class . ':index');
$app->get('/services/add', \App\Controllers\ServiceController::class . ':add');
$app->post('/services/save', \App\Controllers\ServiceController::class . ':save');
$app->get('/services/edit/{id}', \App\Controllers\ServiceController::class . ':edit');
$app->post('/services/update/{id}', \App\Controllers\ServiceController::class . ':update');
$app->get('/services/delete/{id}', \App\Controllers\ServiceController::class . ':delete');


$app->get('/gallery', \App\Controllers\GalleryController::class . ':index');
$app->get('/gallery/add', \App\Controllers\GalleryController::class . ':add');
$app->post('/gallery/save', \App\Controllers\GalleryController::class . ':save');
$app->get('/gallery/edit/{id}', \App\Controllers\GalleryController::class . ':edit');
$app->post('/gallery/update/{id}', \App\Controllers\GalleryController::class . ':update');
$app->get('/gallery/delete/{id}', \App\Controllers\GalleryController::class . ':delete');

$app->group('/staffs', function () use ($app) {
	$app->get('', \App\Controllers\StaffsController::class . ':index');
	$app->get('/add', \App\Controllers\StaffsController::class . ':add');
	$app->post('/save', \App\Controllers\StaffsController::class . ':save');
	$app->get('/edit/{id}', \App\Controllers\StaffsController::class . ':edit');
	$app->post('/update/{id}', \App\Controllers\StaffsController::class . ':update');
	$app->get('/delete/{id}', \App\Controllers\StaffsController::class . ':delete');
});

$app->group('/settings', function () use ($app) {
	$app->get('', \App\Controllers\SettingsController::class . ':index');
	$app->get('/add', \App\Controllers\SettingsController::class . ':add');
	$app->post('/save', \App\Controllers\SettingsController::class . ':save');
	$app->get('/edit/{id}', \App\Controllers\SettingsController::class . ':edit');
	$app->post('/update/{id}', \App\Controllers\SettingsController::class . ':update');
	$app->get('/delete/{id}', \App\Controllers\SettingsController::class . ':delete');
});