<?php

/*
 * For more details about the configuration, see:
 * https://sweetalert2.github.io/#configuration
 */
return [
    'alert' => [
        'position' => 'top-end',
        'timer' => 3000,
        'toast' => true,
        'text' => null,
        'showCancelButton' => false,
        'showConfirmButton' => false,
        'zIndex' => 500,
        'cancelButtonText' => 'Cancelar',
    ],
    'confirm' => [
        'icon' => 'question', // Cambia el ícono por defecto a "question"
        'position' => 'center',
        'toast' => false,
        'timer' => null,
        'showConfirmButton' => true,
        'showCancelButton' => true,
        'cancelButtonText' => 'Cancelar',
        'confirmButtonText' => 'Sí, eliminar',
        'confirmButtonColor' => '#056A70',
        'cancelButtonColor' => '#999999',
    ]
];
