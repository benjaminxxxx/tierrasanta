<?php
// app/Services/Planilla/DerechoHabiente/DerechoHabienteFiltroDTO.php
namespace App\Services\Planilla\DerechoHabiente;
final class DerechoHabienteFiltroDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $empleadoId = null,
        public readonly ?string $tipo = null,
        public readonly ?string $rol = null,
        public readonly ?bool $activo = null,
    ) {}
}