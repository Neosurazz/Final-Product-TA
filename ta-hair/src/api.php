<?php 

use App\Helpers\Utils as U;
use Illuminate\Database\Capsule\Manager as DB;
use App\Controllers\HomeController as HC;
use Symfony\Component\Mime\Email;

$app->group('/api', function ($app) {

	$app->post('/guest', function ($request, $response) {
		$data = $request->getParsedBody();
		$required = ['name','email','phone','service','message'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			return U::error($response, "Email is not valid");
		}

		$service = \App\Models\Service::find($data['service'])->first();

		$msg = '<!DOCTYPE html>
		<html>
		<head>
			<title>New Booking Alert!!!</title>
		</head>
		<body>
			<h1>New Booking Alert!!!</h1>
			<p>A new booking has been made with following details</p>
			<h3>Details</h3>
			<h4>Service: ' . $service->name  . '</h4>
			<p>Name: ' . $data['name'] . '</p>
			<p>Email: ' . $data['email'] . '</p>
			<p>Phone: ' . $data['phone'] . '</p>
			<p>Message: ' . $data['message'] . '</p>
			<p></p>
			<p>Thank you,</p>
			<p>Tasalon.com.au</p>
		</body>
		</html>';

        $email = (new Email())
            ->from('no-reply@tasalon.com.au')
            ->to('admin@tasalon.com.au')
            ->subject('New Booking Alert!!!')
            ->text('View email in html')
            ->html($msg);

        $this->mailer->send($email);

		return U::response($response, [], 'We will contact you soon');
	});

	$app->post('/book', function ($request, $response) {
		$data = $request->getParsedBody();
		$required = ['service','date','time'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}

		$dateStr = $data['date'] . ' ' .$data['time'];

		$dateDt = DateTime::createFromFormat('Y/n/j h:i A', $dateStr);
		$date = $dateDt->format('Y-m-d H:i:s');

		// select valid range
		$day = $dateDt->format('N');
		$hour = (int)$dateDt->format('H');
		$min = (int)$dateDt->format('i');


		switch ($day) {
			// Mon, Tue, Wed 8:30AM-6:30PM
			case 1:
			case 2:
			case 3:
				if (
					($hour <= 8 && $min <30)
					|| ($hour >= 18 && $min > 30)
				) {
					return U::error($response, "Please select time between 8:30AM-6:30PM");
				}
				break;
			// Thus, Fri 8:30AM-7:30PM
			case 4:
			case 5:
				if (
					($hour <= 8 && $min <30)
					|| ($hour >= 19 && $min > 30)
				) {
					return U::error($response, "Please select time between 8:30AM-7:30PM");
				}
				break;
			// Sat 8:30AM-4:00PM
			case 6:
				if (
					($hour <= 8 && $min <30)
					|| ($hour >= 16 && $min > 0)
				) {
					return U::error($response, "Please select time between 8:30AM-4:00PM");
				}
				break;
			// Sun Closed!
			default:
				return U::error($response, "We are closed on Sunday. Sorry Please select another day.");
				break;
		}

		if (isset($data['customer_id'])) {
			$user = \App\Models\User::where('id',$data['customer_id'])->first();
			if (!$user) {
				return U::error($response, "Invalid user id");
			}
			$insert = [
				'datetime' => $date,
				'customer_id' => $data['customer_id'],
				'email' => '',
				'service_id'=> $data['service'],
				'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
			];
		} else {
			$insert = [
				'datetime' => $date,
				'customer_id' => 0,
				'email' => $data['email'],
				'service_id'=> $data['service'],
				'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
			];
		}

		$minutes_to_add = 30;
		$appointmentStartTime = $dateDt->format('Y-m-d H:i:s');
		$appointmentEndingTime = $dateDt->sub(new DateInterval('PT' . $minutes_to_add . 'M'));

		$exists = \App\Models\Appointment::whereBetween('datetime', [$appointmentEndingTime->format('Y-m-d H:i:s'), $appointmentStartTime])->get();

		if ($exists->count() > 0) {
			return U::error($response, "Sorry can't book appointment at your desire time.");
		}

		\App\Models\Appointment::create($insert);

		return U::response($response, [], 'Appointment created successfully');
	});

	$app->get('/store/{user_id}', function ($request, $response, $args) {
		$appointments = \App\Models\Appointment::with('service','user')->orderBy('id','desc')->get();

		$appointments->map(function($item){
			$item->date = $item->datetime->format('Y-m-d');
			$item->time = $item->datetime->format('h:i A');
			$item->createdAt = $item->created_at->format('Y-m-d h:i A');
			$item->customer = ($item->user->name) ? $item->user->name : $item->email;
			$validDt = \Carbon\Carbon::now()->addHours(24);
			$item->deleteAble = false;
		});

		return U::response($response, $appointments);
	});

	$app->get('/appointments/{user_id}', function ($request, $response, $args) {
		
		$user = \App\Models\User::where('id',$args['user_id']);
		if ($user) {
			$appointments = $user->first()->appointments()->with('service')->orderBy('id','desc')->get();

			$appointments->map(function($item){
				$item->date = $item->datetime->format('Y-m-d');
				$item->time = $item->datetime->format('h:i A');
				$item->createdAt = $item->created_at->format('Y-m-d h:i A');
				$validDt = \Carbon\Carbon::now()->addHours(24);
				$item->deleteAble = !$item->datetime->lt($validDt);
			});

			return U::response($response, $appointments);
		} else  {
			return U::error($response, "Invalid user id");
		}
	});

	$app->get('/appointment/{appointment_id}/cancel/{user_id}', function ($request, $response, $args) {
		
		$user = \App\Models\User::where('id',$args['user_id'])->first();
		if ($user) {
			$appointment = \App\Models\Appointment::where([
				['id', '=', $args['appointment_id']],
				['customer_id', '=', $user->id],
			])->first();

			if (!$appointment) {
				return U::error($response, "Invalid appointment id");
			}

			$appointment->delete();

			return U::response($response, [], 'Appointment deleted successfully');

		} else  {
			return U::error($response, "Invalid user id");
		}
	});

	$app->get('/services', function ($request, $response) {
		$services = \App\Models\Service::get();

		$services->map(function($item){
			$item->image_url = U::getBaseUrl().$item->image;
		});

		return U::response($response, $services);
	});

	$app->get('/gallery', function ($request, $response) {
		$gallery = \App\Models\Gallery::get();

		$gallery->map(function($item){
			$item->image_url = U::getBaseUrl().$item->image;
		});

		return U::response($response, $gallery);
	});

	$app->get('/profile/{user_id}', function ($request, $response, $args) {
		$user = \App\Models\User::where('id',$args['user_id']);
		if ($user) {
			return U::response($response, $user->first());
		} else  {
			return U::error($response, "Invalid user id");
		}
	});

	$app->post('/profile/{user_id}', function ($request, $response, $args) {
		$data = $request->getParsedBody();
		$required = ['name','phone','address','email'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			return U::error($response, "Email is not valid");
		}
		$user = \App\Models\User::where([
			['email', '=', $data['email']],
			['id', '<>', $args['user_id']]
		])->first();
		if ($user) {
			return U::error($response, "Email already in use");
		}
		$user = \App\Models\User::where('id',$args['user_id']);
		if ($user) {
			$user = $user->first();
			if (isset($data['password'])) {
				$data['password'] = sha1($data['password']);
			}
			$user->update($data);
			return U::response($response, $user, 'User updated successfully');
		} else  {
			return U::error($response, "Invalid user id");
		}
	});

	$app->post('/password', function ($request, $response) {
		$data = $request->getParsedBody();
		$required = ['password'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}
		$user = \App\Models\User::where('id',$data['user_id']);
		if ($user) {
			$user = $user->first();
			unset($data['user_id']);
			$data['verified'] = 1;
			$data['password'] = sha1($data['password']);
			$user->update($data);
			return U::response($response, $user, 'User updated successfully');
		} else  {
			return U::error($response, "Invalid user id");
		}
	});

	$app->post('/login', function ($request, $response) {
		$data = $request->getParsedBody();
		$required = ['email','password'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}
		$email = $data['email'];
		$password = $data['password'];
		$user = \App\Models\User::where([
		    ['email', '=', $email],
		    ['password', '=', sha1($password)]
	   ])->first();

		$staff = \App\Models\Staff::where([
		    ['email', '=', $email],
	   	])->first();

		if (!empty($staff)) {
			if (password_verify($password, $staff->password)) {
				$staff['is_store'] = true;
				$staff['verified'] = 1;
				unset($staff['password']);
				return U::response($response, $staff);
			}
		}

		if ($user) {
			$user['is_store'] = false;
			unset($user['password']);
			return U::response($response, $user);
		} else  {
			return U::error($response, "Invalid Username or Password");
		}
	});
	$app->get('/token/{user_id}', function ($request, $response, $args) {
		$user = \App\Models\User::where('id',$args['user_id'])->first();
		if (!$user) {
			return U::error($response, "Invalid user id");
		}

		$msg = '<!DOCTYPE html>
		<html>
		<head>
			<title>Your temporary password is here</title>
		</head>
		<body>
			<h1>Your temporary password is here!!!</h1>
			<p>Please login with this temporary password. Once you have logged in you will be required to change this to your password of choice.</p>
			<h3>Details</h3>
			<p>Email: ' . $user->email . '</p>
			<p>Password: ' . $user->token . '</p>
			<p></p>
			<p>Thank you,</p>
			<p>Tasalon.com.au</p>
		</body>
		</html>';

        $email = (new Email())
            ->from('no-reply@tasalon.com.au')
            ->to($user->email)
            ->subject('Your temporary password is here!!!')
            ->text('View email in html')
            ->html($msg);

        $this->mailer->send($email);

        U::response($response, $user, 'Email sent');

	});
	$app->post('/signup', function ($request, $response) {
		$data = $request->getParsedBody();
		$required = ['name', 'address', 'email', 'phone'];
		foreach ($required as $key => $item) {
			if (!isset($data[$item]) || $data[$item] == '') {
				return U::error($response, ucfirst($item) . " is required");
			}
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			return U::error($response, "Email is not valid");
		}
		$user = \App\Models\User::where('email', '=', $data['email'])->first();
		if ($user) {
			return U::error($response, "Email already exists");
		}

		$token = mt_rand(100000, 999999);

		$insert = [
			'name' => $data['name'],
			'phone' => $data['phone'],
			'address' => $data['address'],
			'email' => $data['email'],
			'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
			'password' => sha1($token),
			'token' => $token,
			'verified' => 0
		];

		$user = \App\Models\User::create($insert);

		return U::response($response, $user, 'User signup successfully. Please check your email for login details.');
	});
});