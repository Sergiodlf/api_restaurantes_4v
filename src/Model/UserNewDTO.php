<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class UserNewDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "El campo nombre es obligatorio")]
        public string $name,

        #[Assert\NotBlank(message: "El campo email es obligatorio")]
        #[Assert\Email(message: "El email debe tener un formato válido")]
        public string $email
    ) {}
}