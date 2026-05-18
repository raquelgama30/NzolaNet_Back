<?php

class PostController extends BaseController
{
    private IPostService $service;

    public function __construct(IPostService $service)
    {
        $this->service = $service;
    }

    public function create(string $userId, PostDTO $dto): void
    {
        $result = $this->service->create($userId, $dto);
        $this->json([
            "success" => $result,
            "message" => $result ? "Publicação criada" : "Erro ao criar publicação"
        ], $result ? 201 : 400);
    }


    public function update(string $id, PostDTO $dto): void
    {
        $result = $this->service->update($id, $dto);
        $this->json([
            "success" => $result,
            "message" => $result ? "Publicação atualizada" : "Erro ao atualizar"
        ]);
    }

    public function delete(string $id): void
    {
        $result = $this->service->delete($id);
        $this->json([
            "success" => $result,
            "message" => $result ? "Publicação eliminada" : "Erro ao eliminar"
        ]);
    }

    public function getMyPosts(string $userId, int $page, int $limit): void
    {
        $posts = $this->service->getUserPosts($userId, $page, $limit);
        $this->json(["success" => true, "data" => $posts]);
    }

    // Ver posts de outro utilizador
    // Só mostra se: perfil público OU eu sigo esse utilizador
    public function getPostsDeUtilizador(
        string $authUserId,
        string $targetUserId,
        int $page,
        int $limit
    ): void {
        $posts = $this->service->getPostsDeUtilizador(
            $authUserId,
            $targetUserId,
            $page,
            $limit
        );
        $this->json(["success" => true, "data" => $posts]);
    }

    public function feed(string $userId, int $page, int $limit): void
    {
        $posts = $this->service->getFeed($userId, $page, $limit);
        $this->json(["success" => true, "data" => $posts]);
    }
       
    public function getById(string $id, string $authUserId): void
    {
        $post = $this->service->getById($id, $authUserId);

        if (!$post) {
            $this->json([
                "success" => false,
                "message" => "Publicação não encontrada ou sem permissão para ver"
            ], 404);
        }

        $this->json(["success" => true, "data" => $post]);
    }
}