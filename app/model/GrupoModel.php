<?php

namespace app\model;

class GrupoModel
{
    public function obtenerDatos()
    {
        return [
            'grupo' => 'Grupo 2',
            'integrantes' => [
                ['nombre' => 'FORESI, MATIAS DAMIAN'],
                ['nombre' => 'MENDOZA, EZEQUIEL'],
                ['nombre' => 'THIBAUT, FACUNDO'],
                ['nombre' => 'VODOPIVEC, MARIA PAZ'],
            ],
        ];
    }
}
