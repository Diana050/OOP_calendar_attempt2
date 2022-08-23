<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Entities\Appointment;
use App\Entities\Location;
use App\Views\View;
use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    public function __construct(protected View $view, protected EntityManager $db, protected Auth $auth)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
//        dd($request->getUri()->getQuery());
        $locations = $this->db->getRepository(Appointment::class)->findAll();
        return $this->view->render(new Response, 'home.twig', ['locations' => $locations]);
    }


}

