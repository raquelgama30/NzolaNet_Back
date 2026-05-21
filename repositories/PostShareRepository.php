<?php

class PostShareRepository implements IPostShareRepository
{
    private $conn;

    public function __construct($database)
    {
        $this->conn = $database;
    }

    public function create(PostShare $share): bool
    {
        $sql = "
            INSERT INTO post_shares (
                id, user_id, post_original_id,
                comentario_partilha, criado_em
            ) VALUES (
                :id, :user_id, :post_original_id,
                :comentario_partilha, :criado_em
            )
        ";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":id"                  => $share->id,
            ":user_id"             => $share->user_id,
            ":post_original_id"    => $share->post_original_id,
            ":comentario_partilha" => $share->comentario_partilha,
            ":criado_em"           => $share->criado_em
        ]);
    }

    public function deleteByPost(string $postId): bool
    {
        $sql  = "DELETE FROM post_shares WHERE post_original_id = :post_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":post_id" => $postId]);
    }

    public function deleteByUser(string $userId): bool
    {
        $sql  = "DELETE FROM post_shares WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":user_id" => $userId]);
    }

    public function isShared(string $userId, string $postId): bool
    {
        $sql = "
            SELECT COUNT(*) FROM post_shares
            WHERE user_id = :user_id AND post_original_id = :post_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":user_id" => $userId, ":post_id" => $postId]);
        return $stmt->fetchColumn() > 0;
    }
}