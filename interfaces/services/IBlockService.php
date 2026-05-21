<?php

interface IBlockService
{

    public function block(Block $block): bool;

    public function unblock(string $bloqueadorId, string $bloqueadoId): bool;

    public function isBlocked(string $bloqueadorId, string $bloqueadoId): bool;
    
    public function getBlocked(string $userId): array;
}
