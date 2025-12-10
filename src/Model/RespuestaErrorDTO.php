<?php

namespace App\Model;

class RespuestaErrorDTO
{
    public function __construct(
        public int $code,
        public string $descripcion
    ) {}
}