<?php

namespace App\Controller;

use App\Entity\RestaurantType;
use App\Model\RespuestaErrorDTO;
use App\Model\RestaurantTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}