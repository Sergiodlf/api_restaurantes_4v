<?php

namespace App\Controller;

use DateTime;
use DateTimeZone;
use App\Entity\User;
use App\Model\UserDTO;
use App\Entity\Booking;
use App\Entity\Restaurant;
use App\Model\BookingDTO;
use App\Model\RestauranteDTO;
use App\Model\RespuestaErrorDTO;
use App\Model\RestaurantTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BookingsController extends AbstractController
{

    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/bookings', name: 'post_booking', methods: ['POST'])]
    public function newBooking(Request $request): JsonResponse
    {

        try {
            // Recuperamos del request el Body
            $jsonBody = $request->getContent(); // Obtiene el cuerpo como texto
            $data = json_decode($jsonBody, true); // Lo decodifica a un array asociativo

            /// VALIDACIONES

            // Manejo de errores si el JSON no es válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'JSON inválido'], 400);
            }

            // Valido los campo usuario
            if ($data["user"] == null) {
                $errorMensaje = new RespuestaErrorDTO(20, "El campo usuario es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }
            $usuarioBBDD = $this->entityManager
                ->getRepository(User::class)
                ->find($data["user"]);
            if ($usuarioBBDD == null) {
                $errorMensaje = new RespuestaErrorDTO(201, "El usuario debe de existir");
                return new JsonResponse($errorMensaje, 400);
            }

            // Valido el campo date
            if ($data["date"] == null) {
                $errorMensaje = new RespuestaErrorDTO(21, "El campo fecha es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }
            $format = 'Y-m-d\TH:i:sP';
            $dateBook = DateTime::createFromFormat($format, $data["date"]);
            $dateBook->setTimezone(new DateTimeZone('UTC'));
            $errors = DateTime::getLastErrors();
            if ($dateBook == false && $errors['warning_count'] > 0 && $errors['error_count'] > 0) {
                $errorMensaje = new RespuestaErrorDTO(211, "El campo fecha tiene que tener un formato correcto");
                return new JsonResponse($errorMensaje, 400);
            }
            $tomorrow = new DateTime('tomorrow', new DateTimeZone('UTC'));
            if ($dateBook < $tomorrow) {
                $errorMensaje = new RespuestaErrorDTO(212, "La fecha tiene que ser posterior al día de hoy");
                return new JsonResponse($errorMensaje, 400);
            }

            // Valido el campo people
            if ($data["people"] == null) {
                $errorMensaje = new RespuestaErrorDTO(22, "El campo people es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }
            if ($data["people"] <= 0) {
                $errorMensaje = new RespuestaErrorDTO(221, "El campo people tiene que ser mayor a 0");
                return new JsonResponse($errorMensaje, 400);
            }

            // Valido el campo restaurant
            if ($data["restaurant"] == null) {
                $errorMensaje = new RespuestaErrorDTO(23, "El campo restaurant es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }
            $restaurantBBDD = $this->entityManager
                ->getRepository(Restaurant::class)
                ->find($data["restaurant"]);
            if ($restaurantBBDD == null) {
                $errorMensaje = new RespuestaErrorDTO(201, "El restaurante debe de existir");
                return new JsonResponse($errorMensaje, 400);
            }
            /// Persistimos

            // Creamos la entidad booking
            $newBookingEntity = new Booking();
            $newBookingEntity->setPeople($data["people"]);
            $newBookingEntity->setDate($dateBook);
            $newBookingEntity->setUser($usuarioBBDD);
            $newBookingEntity->setRestaurant($restaurantBBDD);

            // Le dices a Doctrine que quieres persistit el objeto,, todavia no hace nada
            $this->entityManager->persist($newBookingEntity);

            // Aqui es donde confirmas, asi tienes el concepto de transaccion!!!!
            $this->entityManager->flush();

            ///Monto Respuesta
            $restTypeDTO = new RestaurantTypeDTO($newBookingEntity->getRestaurant()->getType()->getId(), $newBookingEntity->getRestaurant()->getType()->getName());
            $restaurantesDTO = new RestauranteDTO($newBookingEntity->getRestaurant()->getId(), $newBookingEntity->getRestaurant()->getName(), $restTypeDTO);
            $userDTO = new UserDTO($newBookingEntity->getUser()->getId(), $newBookingEntity->getUser()->getName(), $newBookingEntity->getUser()->getEmail());
            $bookingDTO = new BookingDTO($newBookingEntity->getId(), $newBookingEntity->getPeople(), $newBookingEntity->getDate()->format($format), $restaurantesDTO, $userDTO);
            return $this->json($bookingDTO);
        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }
}
