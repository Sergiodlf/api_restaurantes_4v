<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class BookingNewDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "El campo usuario es obligatorio")]
        #[Assert\Positive(message: "El ID del usuario debe ser un número positivo")]
        public int $user,

        #[Assert\NotBlank(message: "El campo fecha es obligatorio")]
        public string $date,

        #[Assert\NotBlank(message: "El campo people es obligatorio")]
        #[Assert\Positive(message: "El número de personas debe ser un número positivo")]
        public int $people,

        #[Assert\NotBlank(message: "El campo restaurant es obligatorio")]
        #[Assert\Positive(message: "El ID del restaurante debe ser un número positivo")]
        public int $restaurant
    ) {}
}