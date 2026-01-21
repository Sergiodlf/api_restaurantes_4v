<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\UserDTO;
use App\Model\UserNewDTO;
use App\Model\RespuestaErrorDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class UsersController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}

    #[Route('/users', name: 'post_user', methods: ['POST'])]
    public function newUser(Request $request): JsonResponse
    {
        try {
            // Recuperamos del request el Body
            $jsonBody = $request->getContent();

            // Deserializar el JSON a UserNewDTO
            $userNewDTO = $this->serializer->deserialize($jsonBody, UserNewDTO::class, 'json');

            // Validar el DTO
            $errors = $this->validator->validate($userNewDTO);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], 400);
            }

            // Validaciones adicionales

            // Verificar si el email ya existe
            $existingUser = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $userNewDTO->email]);
            if ($existingUser !== null) {
                $errorMensaje = new RespuestaErrorDTO(301, "El email ya está registrado");
                return new JsonResponse($errorMensaje, 400);
            }

            /// Persistimos

            // Creamos la entidad user
            $newUserEntity = new User();
            $newUserEntity->setName($userNewDTO->name);
            $newUserEntity->setEmail($userNewDTO->email);

            // Le dices a Doctrine que quieres persistir el objeto, todavía no hace nada
            $this->entityManager->persist($newUserEntity);

            // Aquí es donde confirmas, así tienes el concepto de transacción!!!!
            $this->entityManager->flush();

            /// Monto Respuesta
            $userDTO = new UserDTO($newUserEntity->getId(), $newUserEntity->getName(), $newUserEntity->getEmail());
            return $this->json($userDTO);
        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }

    #[Route('/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        try {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            $userDTOs = array_map(fn($user) => new UserDTO($user->getId(), $user->getName(), $user->getEmail()), $users);
            return $this->json($userDTOs);
        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }
}