<?php

class CommentController extends BaseController
{
    private ICommentService $service;

    public function __construct(ICommentService $service)
    {
        $this->service = $service;
    }

    public function create(string $userId, string $postId, string $conteudo): void
    {
        $dto = new CommentDTO(
            id:                "",
            user_id:           $userId,
            post_id:           $postId,
            conteudo:          $conteudo,
            eliminado:         false,
            removido_por_admin:false,
            criado_em:         "",
            atualizado_em:     ""
        );

        $result = $this->service->create($userId, $dto);
        $this->json([
            "success" => $result,
            "message" => $result ? "Comentário criado" : "Erro ao criar comentário"
        ], $result ? 201 : 400);
    }

    public function update(string $commentId, CommentDTO $dto): void
    {
        $result = $this->service->update($commentId, $dto);
        $this->json([
            "success" => $result,
            "message" => $result ? "Comentário atualizado" : "Erro ao atualizar"
        ]);
    }

    public function delete(string $commentId): void
    {
        $result = $this->service->delete($commentId);
        $this->json([
            "success" => $result,
            "message" => $result ? "Comentário eliminado" : "Erro ao eliminar"
        ]);
    }

    // Apenas admin — marca removido_por_admin = true
    public function deleteByAdmin(string $commentId): void
    {
        $result = $this->service->deleteByAdmin($commentId);
        $this->json([
            "success" => $result,
            "message" => $result ? "Comentário removido pelo admin" : "Erro ao remover"
        ]);
    }

    public function getByPost(string $postId, int $page, int $limit): void
    {
        $comments = $this->service->getByPost($postId, $page, $limit);
        $this->json([
            "success" => true,
            "data"    => $comments
        ]);
    }
}