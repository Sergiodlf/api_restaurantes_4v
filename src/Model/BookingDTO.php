<?php

namespace App\Model;

class BookingDTO
{
    public function __construct(
        public int $id,
        public int $people,
        public string $date,
        public RestauranteDTO $restaurant,
        public UserDTO $user
    ) {}
}