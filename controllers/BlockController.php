<?php

class BlockController extends BaseController
{
    private IBlockService $service;

    public function __construct(IBlockService $service)
    {
        $this->service = $service;
    }

    public function block(string $bloqueadorId, string $bloqueadoId): void
    {
        // Verificar auto-bloqueio
        if ($bloqueadorId === $bloqueadoId) {
            $this->json([
                "success" => false,
                "message" => "Não podes bloquear-te a ti próprio"
            ], 400);
        }

        // CORRIGIDO: Gerar UUID sem depender de BaseService
        $block = new Block(
            id: $this->gerarUUID(),  // ← Método privado do controller
            bloqueador_id: $bloqueadorId,
            bloqueado_id: $bloqueadoId,
            criado_em: date("Y-m-d H:i:s")
        );

        $result = $this->service->block($block);

        $this->json([
            "success" => $result,
            "message" => $result ? "Utilizador bloqueado" : "Erro ao bloquear"
        ]);
    }

    public function unblock(string $bloqueadorId, string $bloqueadoId): void
    {
        $result = $this->service->unblock($bloqueadorId, $bloqueadoId);

        $this->json([
            "success" => $result,
            "message" => $result ? "Bloqueio removido" : "Erro ao desbloquear"
        ]);
    }

    public function isBlocked(string $bloqueadorId, string $bloqueadoId): void
    {
        $result = $this->service->isBlocked($bloqueadorId, $bloqueadoId);

        $this->json([
            "success" => true,
            "data" => ["is_blocked" => $result]
        ]);
    }

    public function getBlocked(string $userId): void
    {
        $blocked = $this->service->getBlocked($userId);

        $this->json([
            "success" => true,
            "data" => $blocked
        ]);
    }

    // CORRIGIDO: Método privado para gerar UUID
    private function gerarUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}