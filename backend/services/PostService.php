<?php

declare(strict_types=1);

class PostService extends BaseService implements IPostService
{
    private IPostRepository      $postRepository;
    private IMediaRepository     $mediaRepository;
    private IBazeRepository      $bazeRepository;
    private ICommentRepository   $commentRepository;
    private IUserRepository      $userRepository;
    private IPostShareRepository $postShareRepository;  

    public function __construct(
        IPostRepository      $postRepository,
        IMediaRepository     $mediaRepository,
        IBazeRepository      $bazeRepository,
        ICommentRepository   $commentRepository,
        IUserRepository      $userRepository,
        IPostShareRepository $postShareRepository  
    ) {
        $this->postRepository    = $postRepository;
        $this->mediaRepository   = $mediaRepository;
        $this->bazeRepository    = $bazeRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository    = $userRepository;
        $this->postShareRepository = $postShareRepository;
    }

    public function create(string $userId, PostDTO $dto): bool
    {
        $post = new Post(
            id:            $this->generateUUID(),
            user_id:       $userId,
            conteudo:      $dto->conteudo,
            eliminado:     false,
            criado_em:     date("Y-m-d H:i:s"),
            atualizado_em: date("Y-m-d H:i:s")
        );
        return $this->postRepository->create($post);
    }

    public function getById(string $postId, string $authUserId): ?PostComMediaDTO
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) return null;

        $podeVer = $this->postRepository->podeVerPosts($authUserId, $post->user_id);
        if (!$podeVer) return null;

        return $this->enriquecerComMedia($post);
    }

    public function getFeed(string $userId, int $page, int $limit): array
    {
        if ($this->postRepository->hasFollowing($userId)) {
            $posts = $this->postRepository->getFollowingFeed($userId, $page, $limit);
        } else {
            $posts = $this->postRepository->getPublicFeed($page, $limit);
        }
        return array_map([$this, 'enriquecerComMedia'], $posts);
    }

    public function getUserPosts(string $userId, int $page, int $limit): array
    {
        $posts = $this->postRepository->getFeedByUser($userId, $page, $limit);
        return array_map([$this, 'enriquecerComMedia'], $posts);
    }

    public function getPostsDeUtilizador(
        string $authUserId,
        string $targetUserId,
        int    $page,
        int    $limit
    ): array {
        $podeVer = $this->postRepository->podeVerPosts($authUserId, $targetUserId);
        if (!$podeVer) return [];

        $posts = $this->postRepository->getFeedByUser($targetUserId, $page, $limit);
        return array_map([$this, 'enriquecerComMedia'], $posts);
    }

    private function enriquecerComMedia(PostDTO $post): PostComMediaDTO
    {
        $autor = $this->userRepository->findById($post->user_id);
        $mediaArray = $this->mediaRepository->findByPost($post->id);
        $media = $mediaArray[0] ?? null;

        $totalBazes       = $this->bazeRepository->countByPost($post->id);
        $totalComentarios = $this->commentRepository->countByPost($post->id);

        return new PostComMediaDTO(
            id:                $post->id,
            user_id:           $post->user_id,
            autor_nome:        $autor?->nome          ?? "Utilizador removido",
            autor_username:    $autor?->username       ?? null,
            autor_foto_perfil: $autor?->foto_perfil    ?? null,
            conteudo:          $post->conteudo,
            eliminado:         $post->eliminado,
            criado_em:         $post->criado_em,
            atualizado_em:     $post->atualizado_em,
            total_bazes:       $totalBazes,
            total_comentarios: $totalComentarios,
            media:             $media
        );
    }

    private function apagarFicheiro(string $url): void
    {
        $baseUrl = "http://localhost:8081/NzolaNet/backend/";
        $caminho = str_replace($baseUrl, "", $url);

        $raiz     = dirname(__DIR__);
        $ficheiro = $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $caminho);

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }
    }

    public function update(string $postId, PostDTO $dto): bool
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) throw new Exception("Publicação não encontrada");
        if ($post->user_id !== $dto->user_id) throw new Exception("Não tens permissão para editar");

        return $this->postRepository->update($postId, $dto);
    }

    // DELETE do utilizador (verifica ownership)
    public function delete(string $postId, string $authUserId): bool
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) throw new Exception("Publicação não encontrada");
        if ($post->user_id !== $authUserId) throw new Exception("Não tens permissão para eliminar");

        return $this->executarDeleteCompleto($postId);
    }

    // DELETE do admin (bypass ownership)
    public function deleteByAdmin(string $postId): bool
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) throw new Exception("Publicação não encontrada");

        return $this->executarDeleteCompleto($postId);
    }

    // Método privado que executa a cascata completa
    private function executarDeleteCompleto(string $postId): bool
    {
        // 1. Apagar TODOS os ficheiros físicos de media
        $mediaArray = $this->mediaRepository->findByPost($postId);
        foreach ($mediaArray as $media) {
            $this->apagarFicheiro($media->url);
        }
        $this->mediaRepository->deleteByPost($postId);

        // 2. Apagar interações
        $this->bazeRepository->deleteByPost($postId);
        $this->commentRepository->deleteByPost($postId);
        $this->postShareRepository->deleteByPost($postId);  // NOVO: apaga shares

        // 3. Soft delete do post
        return $this->postRepository->delete($postId);
    }
}