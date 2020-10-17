<?php 
namespace App\Controllers;

use Psr\Container\ContainerInterface;

class CoreController {
	protected $container;

   public function __construct(ContainerInterface $container) {
       $this->container = $container;
   }

   public function view($response, $view, $args = []) {
   		return $this->container->view->render($response, $view, $args);
   }

   public function mail() {
   		return $this->container->get('mailer');
   }

}