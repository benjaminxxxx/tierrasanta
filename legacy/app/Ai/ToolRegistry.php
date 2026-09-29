<?php
namespace App\Ai;

class ToolRegistry
{
    public static function all(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'INSERTAR_MAQUINARIAS',
                    'description' => 'Registrar nuevas maquinarias',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'maquinarias' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'nombre' => ['type' => 'string'],
                                        'alias_blanco' => ['type' => 'string'],
                                    ],
                                    'required' => ['nombre'],
                                ],
                            ],
                        ],
                        'required' => ['maquinarias'],
                    ],
                ],
            ],
        ];
    }
}
