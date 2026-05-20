<?php

declare(strict_types=1);

class CommentService extends BaseService implements ICommentService
{
    private ICommentRepository   $commentRepository;
    private INotificationService $notificationService;
    private IPostRepository      $postRepository;
    private IBlockRepository     $blockRepository;

    public function __construct(
        ICommentRepository   $commentRepository,
        INotificationService $notificationService,
        IPostRepository      $postRepository,
        IBlockRepository     $blockRepository
    ) {
        $this->commentRepository   = $commentRepository;
        $this->notificationService = $notificationService;
        $this->postRepository      = $postRepository;
        $this->blockRepository     = $blockRepository;
    }

    public function create(string $userId, CommentDTO $dto): bool
    {
        $post = $this->postRepository->findById($dto->post_id);
        if (!$post) throw new Exception("Publicação não encontrada");

        // NOVO: Verificar blocks
        if ($this->blockRepository->isBlocked($post->user_id, $userId)) {
            throw new Exception("Não podes comentar nesta publicação");
        }
        if ($this->blockRepository->isBlocked($userId, $post->user_id)) {
            throw new Exception("Desbloqueia o autor primeiro");
        }

        $comment = new Comment(
            id: $this->generateUUID(),
            user_id: $userId,
            post_id: $dto->post_id,
            conteudo: $dto->conteudo,
            eliminado: false,
            removido_por_admin: false,
            criado_em: date("Y-m-d H:i:s"),
            atualizado_em: date("Y-m-d H:i:s")
        );

        $created = $this->commentRepository->create($comment);

        if ($created && $post->user_id !== $userId) {
            $this->notificationService->create(new NotificationDTO(
                id: "",
                destinatario_id: $post->user_id,
                remetente_id: $userId,
                tipo: "comentario",
                referencia_id: $dto->post_id,
                referencia_tipo: "post",
                agrupada: false,        // ← NOVO
                contagem_agrupada: 1,
                lida: false,
                criado_em: ""
            ));
        }

        return $created;
    }

    public function deleteByAdmin(string $commentId): bool
    {
        return $this->commentRepository->deleteByAdmin($commentId);
    }

    public function deleteByPost(string $postId): bool
    {
        return $this->commentRepository->deleteByPost($postId);
    }

    public function getByPost(string $postId, int $page, int $limit): array
    {
        return $this->commentRepository->getByPost($postId, $page, $limit);
    }

    public function getById(string $commentId): ?CommentDTO
    {
        return $this->commentRepository->findById($commentId);
    }

    public function update(string $commentId, CommentDTO $dto): bool
    {
        $comment = $this->commentRepository->findById($commentId);
        if (!$comment) throw new Exception("Comentário não encontrado");
        if ($comment->user_id !== $dto->user_id) throw new Exception("Não tens permissão");

        return $this->commentRepository->update($commentId, $dto);
    }

    public function delete(string $commentId, string $authUserId): bool
    {
        $comment = $this->commentRepository->findById($commentId);
        if (!$comment) throw new Exception("Comentário não encontrado");
        if ($comment->user_id !== $authUserId) throw new Exception("Não tens permissão");

        return $this->commentRepository->delete($commentId);
    }
}
