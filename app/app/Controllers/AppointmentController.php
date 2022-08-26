<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use App\Auth\Auth;
use App\Entities\Location;
use App\Entities\User;
use App\Entities\Appointment;
use App\Session\Flash;
use App\Views\View;
use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\NoReturn;
use Laminas\Diactoros\Response;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AppointmentController extends Controller
{
    public function __construct(protected View $view, protected EntityManager $db, protected Auth $auth, protected Router $router, protected Flash $flash)
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
            return redirect($this->router->getNamedRoute('home')->getPath());
        }
        return redirect($this->router->getNamedRoute('home')->getPath());
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

        $numberOfAppointmentsToday = $this->db->getRepository(Appointment::class)->count([
            'date' => \DateTime::createFromFormat('Y-m-d', $data['date']),
            'user' => $this->auth->user()
        ]);

        $numberOfAppointments = $this->db->getRepository(Appointment::class)->count([
            'date' => \DateTime::createFromFormat('Y-m-d', $data['date']),
            'location' => $currentLocation
        ]);

        if (new \DateTime('today') > \DateTime::createFromFormat('Y-m-d', $data['date'])) {
            $this->flash->now('error', 'Please select a future day!');
            return false;
        } else if ($numberOfAppointments >= $currentLocation->maxCapacity) {
            $this->flash->now('error', 'Unfortunately there are no more places available for you!');
            return false;
        } else if ($numberOfAppointmentsToday > 0) {
            $this->flash->now('error', 'You have already made an appointment for this date or this location!');
            return false;
        } else
            return true;
    }
}

//simpfony 6