<?php

interface IPostShareRepository {

    public function create(PostShare $share): bool;

    public function deleteByPost(string $postId): bool;

    public function deleteByUser(string $userId): bool;

    public function isShared(string $userId, string $postId): bool;
    
}