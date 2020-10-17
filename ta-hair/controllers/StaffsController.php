<?php 
namespace App\Controllers;

use App\Models\Category;
use App\Models\Staff;

class StaffsController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
    	$staffs = Staff::get();
    	$this->view($response, 'staffs/index.twig', compact('staffs'));
    }

    public function add($request, $response, $args) {
    	$this->view($response, 'staffs/form.twig');
    }

    public function save($request, $response, $args) {
        $data = $this->getData($request);
        $data['is_admin'] = $data['is_admin'] ?? 0;
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    	$staff = Staff::create($data);
		$staff->save();
    	return $response->withRedirect('/staffs');
    }

    public function edit($request, $response, $args) {
    	$staff = Staff::findOrFail($args['id']);
    	$this->view($response, 'staffs/form.twig', compact('staff'));
    }

    public function update($request, $response, $args) {
    	$staff = Staff::findOrFail($args['id']);
        $data = $this->getData($request);
        $data['is_admin'] = $data['is_admin'] ?? 0;
        if ($data['password'] != '') {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        $staff->update($data);
    	return $response->withRedirect('/staffs');
    }

    public function delete($request, $response, $args) {
    	$staff = Staff::findOrFail($args['id']);
        $staff->delete();
    	return $response->withRedirect('/staffs');
    }

    public function getData($request) {
        $data = $request->getParsedBody();
        return $data;
    }
}