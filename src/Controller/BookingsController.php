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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Model\BookingNewDTO;

final class BookingsController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}

    #[Route('/bookings', name: 'post_booking', methods: ['POST'])]
    public function newBooking(Request $request): JsonResponse
    {
        try {
            // Recuperamos del request el Body
            $jsonBody = $request->getContent();

            // Deserializar el JSON a BookingNewDTO
            $bookingNewDTO = $this->serializer->deserialize($jsonBody, BookingNewDTO::class, 'json');

            // Validar el DTO
            $errors = $this->validator->validate($bookingNewDTO);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], 400);
            }

            // Validaciones adicionales

            // Valido el campo date
            $format = 'Y-m-d\TH:i:sP';
            $dateBook = DateTime::createFromFormat($format, $bookingNewDTO->date);
            if ($dateBook === false) {
                $errorMensaje = new RespuestaErrorDTO(211, "El campo fecha tiene que tener un formato correcto");
                return new JsonResponse($errorMensaje, 400);
            }
            $dateBook->setTimezone(new DateTimeZone('UTC'));
            $tomorrow = new DateTime('tomorrow', new DateTimeZone('UTC'));
            if ($dateBook < $tomorrow) {
                $errorMensaje = new RespuestaErrorDTO(212, "La fecha tiene que ser posterior al día de hoy");
                return new JsonResponse($errorMensaje, 400);
            }

            // Valido el campo user
            $usuarioBBDD = $this->entityManager
                ->getRepository(User::class)
                ->find($bookingNewDTO->user);
            if ($usuarioBBDD == null) {
                $errorMensaje = new RespuestaErrorDTO(201, "El usuario debe de existir");
                return new JsonResponse($errorMensaje, 400);
            }

            // Valido el campo restaurant
            $restaurantBBDD = $this->entityManager
                ->getRepository(Restaurant::class)
                ->find($bookingNewDTO->restaurant);
            if ($restaurantBBDD == null) {
                $errorMensaje = new RespuestaErrorDTO(231, "El restaurante debe de existir");
                return new JsonResponse($errorMensaje, 400);
            }

            /// Persistimos

            // Creamos la entidad booking
            $newBookingEntity = new Booking();
            $newBookingEntity->setPeople($bookingNewDTO->people);
            $newBookingEntity->setDate($dateBook);
            $newBookingEntity->setUser($usuarioBBDD);
            $newBookingEntity->setRestaurant($restaurantBBDD);

            // Le dices a Doctrine que quieres persistir el objeto, todavía no hace nada
            $this->entityManager->persist($newBookingEntity);

            // Aquí es donde confirmas, así tienes el concepto de transacción!!!!
            $this->entityManager->flush();

            // Monto Respuesta
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
