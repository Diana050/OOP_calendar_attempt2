<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use App\Auth\Auth;
use App\Entities\Location;
use App\Entities\User;
use App\Entities\Appointment;
use App\Views\View;
use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\NoReturn;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class AppointmentController extends Controller
{
    public function __construct(protected View $view, protected EntityManager $db, protected Auth $auth)
    {

    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $locations = $this->db->getRepository(Location::class)->findAll();

        return $this->view->render(new Response, 'templates/appointment.twig', ['locations' => $locations]);
    }

//create an appointment

    public function store(ServerRequestInterface $request): ResponseInterface

    {
        $data = $this->validateAppointment($request);

        if ($this->validateDateAndLocation($data)) {
            $this->createAppointment($data);

            return $this->view->render(new Response, 'home.twig');
            //return redirect($this->router->getNamedRoute('home')->getPath());
        }
        return $this->view->render(new Response, 'home.twig');
    }

    protected function createAppointment(array $data): Appointment
    {
        $appointment = new Appointment();
        $locations = $this->db->getRepository(Location::class)->find($data['location']);
        $data = \DateTime::createFromFormat('Y-m-d', $data['date']);

        $appointment->fill([
            'date' => $data,
            'user' => $this->auth->user(),
            'location' => $locations,
        ]);
        //dd($data);
        $this->db->persist($appointment);
        $this->db->flush();
        return $appointment;
    }

    private function validateAppointment(ServerRequestInterface $request): array
    {
        return $this->validate($request, [
            'date' => ['required'],
            'location' => ['required'],
        ]);

    }

    private function validateDateAndLocation(array $data): bool
    {
        $currentLocation = $this->db->getRepository(Location::class)->find($data['location']);

        $aptToday = $this->db->getRepository(Appointment::class)->count([
            'date' => \DateTime::createFromFormat('Y-m-d', $data['date']),
            'user' => $this->auth->user()
        ]);
        if (($currentLocation->current_apt > $currentLocation->max_apt) || ($aptToday > 0))
            return false;
        else {
            return true;
        }
    }

}