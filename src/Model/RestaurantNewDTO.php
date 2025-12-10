<?php

namespace App\Model;

class RestaurantNewDTO
{
    public function __construct(
        public string $name,
        public int $resType
    ) {}
}