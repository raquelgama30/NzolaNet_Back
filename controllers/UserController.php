<?php

class UserController extends BaseController
{
    private IUserService $service;

    public function __construct(IUserService $service)
    {
        $this->service = $service;
    }

    public function getById(string $id): void
    {
        $user = $this->service->getById($id);
        if (!$user) {
            $this->json(["success" => false, "message" => "Utilizador não encontrado"], 404);
        }
        $this->json(["success" => true, "data" => $user]);
    }

    public function updateProfile(string $id, UpdateUserDTO $dto): void
    {
        $result = $this->service->updateProfile($id, $dto);
        $this->json([
            "success" => $result,
            "message" => $result ? "Perfil atualizado" : "Erro ao atualizar perfil"
        ]);
    }

    public function alterarPassword(
        string $userId,
        string $passwordAtual,
        string $passwordNova
    ): void {
        $result = $this->service->alterarPassword(
            $userId,
            $passwordAtual,
            $passwordNova
        );
        $this->json([
            "success" => $result,
            "message" => $result ? "Password alterada" : "Password atual incorreta"
        ]);
    }

    public function delete(string $id): void
    {
        $result = $this->service->deleteUser($id);
        $this->json([
            "success" => $result,
            "message" => $result ? "Conta desativada" : "Erro ao desativar"
        ]);
    }

    public function pessoasQueTalvezConheca(string $userId, int $limit = 10): void
    {
        try {
            $resultado = $this->service->obterSugestoes($userId, $limit); // ✅ $this->service
            echo json_encode([
                "success" => true,
                "data" => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
    public function eliminar(string $id): void
    {
        $result = $this->service->eliminarPermanente($id);
        $this->json([
            "success" => $result,
            "message" => $result ? "Utilizador eliminado permanentemente" : "Erro ao eliminar utilizador"
        ]);
    }

    public function pesquisar(string $query): void
    {
        $users = $this->service->pesquisar($query);
        $this->json(["success" => true, "data" => $users]);
    }
    public function removerFotoPerfil(string $id): void
    {
        $result = $this->service->removerFotoPerfil($id);

        $this->json([
            "success" => $result,
            "message" => $result
                ? "Foto de perfil removida"
                : "Erro ao remover foto de perfil"
        ]);
    }

    public function removerFotoCapa(string $id): void
    {
        $result = $this->service->removerFotoCapa($id);

        $this->json([
            "success" => $result,
            "message" => $result
                ? "Foto de capa removida"
                : "Erro ao remover foto de capa"
        ]);
    }
    // ── Admin ──────────────────────────────────

    public function listarTodos(): void
    {
        $users = $this->service->listarTodos();
        $this->json(["success" => true, "data" => $users]);
    }

    public function ativar(string $id): void
    {
        $result = $this->service->ativar($id);
        $this->json([
            "success" => $result,
            "message" => $result ? "Conta ativada" : "Erro ao ativar"
        ]);
    }

    public function desativar(string $id): void
    {
        $result = $this->service->desativar($id);
        $this->json([
            "success" => $result,
            "message" => $result ? "Conta desativada" : "Erro ao desativar"
        ]);
    }

    public function getAdminMetrics(): array
    {
        return $this->userRepository->getAdminMetrics();
    }
}
