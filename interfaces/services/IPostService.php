<?php

interface IPostService {

    public function create(string $userId, PostDTO $dto): bool;
    public function update(string $postId, PostDTO $dto): bool;

    public function delete(string $postId, string $authUserId): bool;

    /** @return PostComMediaDTO[] */
    public function getFeed(string $userId,string $authUserId, int $page, int $limit): array;

    /** @return PostComMediaDTO[] */
    public function getUserPosts(string $userId,string $authUserId, int $page, int $limit): array;

    /** @return PostComMediaDTO[] */
    public function getPostsDeUtilizador(
        string $authUserId,
        string $targetUserId,
        int    $page,
        int    $limit
    ): array;
    public function getById(string $postId, string $authUserId): ?PostComMediaDTO;
    public function deleteByAdmin(string $postId): bool;
    public function getExplore(string $authUserId, int $page, int $limit): array;
}