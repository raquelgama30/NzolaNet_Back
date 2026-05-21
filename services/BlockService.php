<?php

declare(strict_types=1);

class BlockService extends BaseService implements IBlockService
{
    private IBlockRepository $blockRepository;

    public function __construct(IBlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    public function block(Block $block): bool
    {
        // Verificar auto-bloqueio (defesa em profundidade - BD também tem constraint)
        if ($block->bloqueador_id === $block->bloqueado_id) {
            throw new Exception("Não podes bloquear-te a ti próprio");
        }

        // Verificar se já está bloqueado
        if ($this->blockRepository->isBlocked($block->bloqueador_id, $block->bloqueado_id)) {
            throw new Exception("Este utilizador já está bloqueado");
        }

        return $this->blockRepository->block($block);
    }

    public function unblock(string $bloqueadorId, string $bloqueadoId): bool
    {
        return $this->blockRepository->unblock($bloqueadorId, $bloqueadoId);
    }

    public function isBlocked(string $bloqueadorId, string $bloqueadoId): bool
    {
        return $this->blockRepository->isBlocked($bloqueadorId, $bloqueadoId);
    }

    public function getBlocked(string $userId): array
    {
        return $this->blockRepository->getBlocked($userId);
    }

    // Método adicional para verificar se há bloqueio mútuo
    public function hasMutualBlock(string $userId1, string $userId2): bool
    {
        return $this->isBlocked($userId1, $userId2) || $this->isBlocked($userId2, $userId1);
    }
}