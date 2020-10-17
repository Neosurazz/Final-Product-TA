<?php 
namespace App\Helpers;

use App\Helpers\Utils;

class TwigHelpers extends \Slim\Views\TwigExtension
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'TwigHelpers';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('isAdmin', array($this, 'isAdmin'))
        ];
    }

    public function isAdmin ()
    {
       return Utils::isAdmin();
    }
}