<?php

namespace App\Controller;

use App\Entity\RestaurantType;
use App\Model\RespuestaErrorDTO;
use App\Model\RestaurantTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 

final class RestaurantTypesController extends AbstractController
{

    public function __construct(private EntityManagerInterface $entityManager) {}
    
    #[Route('/restaurant-types', name: 'app_restaurant_types', methods:['GET'])]
    public function getAllRestaurantTypes(): JsonResponse
    {
        try {

            // Recupero la información de BBDD
            $tiposRestaurantesBBDD = $this->entityManager
                                        ->getRepository(RestaurantType::class)
                                        ->findAll();
            
            // Convierto de Entidades a DTO
            $tipoRestaurantesDTO = [];
            foreach ($tiposRestaurantesBBDD as $tipoRestaurantesEntidad) {
                $tipoRestaurantesDTO[] = new RestaurantTypeDTO($tipoRestaurantesEntidad->getId(), $tipoRestaurantesEntidad->getName());
            }

            return $this->json($tipoRestaurantesDTO);

        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }

    #[Route('/restaurant-types', name: 'post_restaurant_type', methods:['POST'])]
    public function newRestaurantType(Request $request): JsonResponse
    {
        try {
            // Recuperamos del request el Body
            $jsonBody = $request->getContent(); // Obtiene el cuerpo como texto
            $data = json_decode($jsonBody, true); // Lo decodifica a un array asociativo

            // Manejo de errores si el JSON no es válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'JSON inválido'], 400);
            }

            // Validaciones
            if (empty($data['name'])) {
                $errorMensaje = new RespuestaErrorDTO(10, "El campo name es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }

            // Compruebo duplicados por nombre
            $existing = $this->entityManager
                             ->getRepository(RestaurantType::class)
                             ->findOneBy(['name' => $data['name']]);
            if ($existing) {
                $errorMensaje = new RespuestaErrorDTO(11, "Tipo de restaurante ya existe");
                return new JsonResponse($errorMensaje, 409);
            }

            // Creo y persisto entidad
            $tipo = new RestaurantType();
            $tipo->setName($data['name']);

            $this->entityManager->persist($tipo);
            $this->entityManager->flush();

            $tipoDTO = new RestaurantTypeDTO($tipo->getId(), $tipo->getName());

            return new JsonResponse($tipoDTO, 201);

        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }
}


