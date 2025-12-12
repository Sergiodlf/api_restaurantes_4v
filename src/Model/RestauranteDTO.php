<?php

namespace App\Model;

class RestauranteDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?RestaurantTypeDTO $resType
    ) {}
}