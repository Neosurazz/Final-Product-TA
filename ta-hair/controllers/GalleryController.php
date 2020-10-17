<?php 
namespace App\Controllers;

use App\Models\Gallery;

class GalleryController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
    	$galleries = Gallery::get();
    	$this->view($response, 'galleries/index.twig', compact('galleries'));
    }

    public function add($request, $response, $args) {
    	$this->view($response, 'galleries/form.twig');
    }

    public function save($request, $response, $args) {
    	$gallery = Gallery::create($request->getParsedBody());

        $storage = new \Upload\Storage\FileSystem(__DIR__.'/../images/galleries/');
        $file = new \Upload\File('image', $storage);

        $new_filename = uniqid();
        $file->setName($new_filename);

        $file->addValidations(array(
            new \Upload\Validation\Size('5M')
        ));

        try {
            $file->upload();
        } catch (\Exception $e) { }

        $gallery->image = $file->getNameWithExtension();
        $gallery->save();

    	return $response->withRedirect('/gallery');
    }

    public function edit($request, $response, $args) {
    	$gallery = Gallery::findOrFail($args['id']);
    	$this->view($response, 'galleries/form.twig', compact('gallery'));
    }

    public function update($request, $response, $args) {
    	$gallery = Gallery::findOrFail($args['id']);
    	$gallery->update($request->getParsedBody());

        if ($_FILES['image']['name'] != '') {
            $storage = new \Upload\Storage\FileSystem(__DIR__.'/../images/galleries');
            $file = new \Upload\File('image', $storage);

            $new_filename = uniqid();
            $file->setName($new_filename);
            $file->addValidations(array(
                new \Upload\Validation\Size('5M')
            ));
            try {
                $file->upload();
            } catch (\Exception $e) { }
            $gallery->image = $file->getNameWithExtension();
            $gallery->update();
        }

    	return $response->withRedirect('/gallery');
    }

    public function delete($request, $response, $args) {
    	$gallery = Gallery::findOrFail($args['id']);
    	$gallery->delete();
    	return $response->withRedirect('/gallery');
    }
}