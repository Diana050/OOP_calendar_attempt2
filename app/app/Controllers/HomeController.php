<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Entities\Appointment;
use App\Entities\Location;
use App\Session\Flash;
use App\Views\View;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    public function __construct(protected View $view, protected EntityManager $db, protected Auth $auth , protected Flash $flash)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {

        $getParams = $request->getQueryParams();
        $appointments = [];

        $locations = $this->db->getRepository(Location::class)->findAll();

        if (array_key_exists("date", $getParams)) {

            $date = $getParams['date'];
            $location = $getParams['location'];
            $location = $this->db->getRepository(Location::class)->findOneBy(['id'=> $location]);
            $appointments = $this->db->getRepository(Appointment::class)->matching(
                Criteria::create()->where(Criteria::expr()->eq('date', \DateTime::createFromFormat('Y-m-d', $date)))->
                andWhere(Criteria::expr()->eq('location', $location))
            )->getValues();
        }
        return $this->view->render(new Response, 'home.twig', ['appointments' => $appointments,'locations' => $locations]);
    }
}

