<?php 
namespace App\Controllers;

use App\Models\Service;

class ServiceController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
    	$services = Service::get();
    	$this->view($response, 'services/index.twig', compact('services'));
    }

    public function add($request, $response, $args) {
    	$this->view($response, 'services/form.twig');
    }

    public function save($request, $response, $args) {
    	$service = Service::create($request->getParsedBody());

        $storage = new \Upload\Storage\FileSystem(__DIR__.'/../images/services/');
        $file = new \Upload\File('image', $storage);

        $new_filename = uniqid();
        $file->setName($new_filename);

        $file->addValidations(array(
            new \Upload\Validation\Size('5M')
        ));

        try {
            $file->upload();
        } catch (\Exception $e) { }

        $service->image = $file->getNameWithExtension();
        $service->save();

    	return $response->withRedirect('/services');
    }

    public function edit($request, $response, $args) {
    	$service = Service::findOrFail($args['id']);
    	$this->view($response, 'services/form.twig', compact('service'));
    }

    public function update($request, $response, $args) {
    	$service = Service::findOrFail($args['id']);
    	$service->update($request->getParsedBody());

        if ($_FILES['image']['name'] != '') {
            $storage = new \Upload\Storage\FileSystem(__DIR__.'/../images/services');
            $file = new \Upload\File('image', $storage);

            $new_filename = uniqid();
            $file->setName($new_filename);
            $file->addValidations(array(
                new \Upload\Validation\Size('5M')
            ));
            try {
                $file->upload();
            } catch (\Exception $e) { }
            $service->image = $file->getNameWithExtension();
            $service->update();
        }

    	return $response->withRedirect('/services');
    }

    public function delete($request, $response, $args) {
    	$service = Service::findOrFail($args['id']);
    	$service->delete();
    	return $response->withRedirect('/services');
    }
}