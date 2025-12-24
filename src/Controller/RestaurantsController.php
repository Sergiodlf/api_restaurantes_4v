<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Model\RespuestaErrorDTO;
use App\Model\RestauranteDTO;
use App\Model\RestaurantTypeDTO;
use App\Model\RestaurantNewDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpFoundation\Request;

final class RestaurantsController extends AbstractController
{

    private $restaurantes = [];
    private $restaurantesItalianos = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $restauranteItaliano = new RestaurantTypeDTO(1, "Italiano");
        $restaurante1 = new RestauranteDTO(1, "La tagliatella", $restauranteItaliano);
        $restaurante2 = new RestauranteDTO(2, "La mamma", $restauranteItaliano);
        $this->restaurantes = [$restaurante1, $restaurante2];

        $this->restaurantesItalianos = [$restaurante1];
    }

    #[Route('/restaurants', name: 'app_restaurants', methods:['GET'])]
    public function getRestaurantes(#[MapQueryParameter()] ?string $tipo  = null): JsonResponse
    {

        try {

            // Valido el tipo, que debe de ser un entero
            if ($tipo != null && !$this->esEnteroPositivo($tipo)){
                $errorMensaje = new RespuestaErrorDTO(10, "Validación tipo restaurante invalido");
                return new JsonResponse($errorMensaje, 400);
            }

            // Si hay tipo entonces busco por tipo, sino busco cualquiera
            if ($tipo != null){
                $tipoEntero = (int)$tipo;
                // Recupero la información de BBDD
                $restaurantesBBDD = $this->entityManager
                                            ->getRepository(Restaurant::class)
                                            ->findByType($tipoEntero);
            }
            else{
                // Recupero la información de BBDD
                $restaurantesBBDD = $this->entityManager
                                            ->getRepository(Restaurant::class)
                                            ->findAll();
            }

            // Convierto de Entidades a DTO
            $restaurantesDTO = [];
            foreach ($restaurantesBBDD as $restautanteEntidad) {
                $restTypeDTO = new RestaurantTypeDTO($restautanteEntidad->getType()->getId(), $restautanteEntidad->getType()->getName());
                $restaurantesDTO[] = new RestauranteDTO($restautanteEntidad->getId(), $restautanteEntidad->getName(), $restTypeDTO);
            }

            return $this->json($restaurantesDTO);

        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }

    }

    #[Route('/restaurants', name: 'post_restaurants', methods:['POST'])]
    public function newRestaurants(Request $request): JsonResponse
    {

        try {
            // Recuperamos del request el Body
            $jsonBody = $request->getContent(); // Obtiene el cuerpo como texto
            $data = json_decode($jsonBody, true); // Lo decodifica a un array asociativo

            // Manejo de errores si el JSON no es válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'JSON inválido'], 400);
            }

            // Hago validaciones pertinentes y me creo mi Objeto de Modelo RestauranteNewDTO
            if ($data["name"] == null){
                $errorMensaje = new RespuestaErrorDTO(10, "El campo nombre es obligatorio");
                return new JsonResponse($errorMensaje, 400);
            }
            $restauranteNuevo = new RestaurantNewDTO($data["name"], $data["res-type"]);

            // Inserto el objeto en nuestro array de restaurantes, HCoded 
            $restauranteInsertado = new RestauranteDTO(sizeof($this->restaurantes)+1, $restauranteNuevo->name,  new RestaurantTypeDTO($restauranteNuevo->resType,"Italiano"));
            array_push($this->restaurantes, $restauranteInsertado);

            //Contesto
            return $this->json($this->restaurantes[sizeof($this->restaurantes)-1]);

        } catch (\Throwable $th) {
            $errorMensaje = new RespuestaErrorDTO(1000, "Error General");
            return new JsonResponse($errorMensaje, 500);
        }
    }

    private function esEnteroPositivo(string $valor): bool {
        // Comprueba que todos los caracteres sean dígitos
        if (!ctype_digit($valor)) {
            return false;
        }

        // Convierte a entero y verifica que sea mayor que 0
        return (int)$valor > 0;
    }



}