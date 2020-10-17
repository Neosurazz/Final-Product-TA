<?php 
namespace App\Helpers;

class Utils {
	public static function getBaseUrl() {
		return 'http://10.0.2.2/ta-hair/public';
	}
	public static function isAdmin() {
        return true;
	}

	public static function response($response, $data = [], $msg = '') {
		return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status' => true, 'msg' => $msg, 'data' => $data]));
	}

	public static function error($response, $msg = '', $data = []) {
		return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status' => false, 'msg' => $msg, 'data' => $data]));
	}
}