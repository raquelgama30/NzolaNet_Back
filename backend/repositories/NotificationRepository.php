<?php

class NotificationRepository implements INotificationRepository
{
    private $conn;

    public function __construct($database)
    {
        $this->conn = $database;
    }

    public function create(Notification $notification): bool
    {
        $sql = "
            INSERT INTO notifications (
                id,
                destinatario_id,
                remetente_id,
                tipo,
                referencia_id,
                referencia_tipo,
                lida,
                criado_em
            ) VALUES (
                :id,
                :destinatario_id,
                :remetente_id,
                :tipo,
                :referencia_id,
                :referencia_tipo,
                0,
                :criado_em
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":id" => $notification->id,
            ":destinatario_id" => $notification->destinatario_id,
            ":remetente_id" => $notification->remetente_id,
            ":tipo" => $notification->tipo,
            ":referencia_id" => $notification->referencia_id,
            ":referencia_tipo" => $notification->referencia_tipo,
            ":criado_em" => $notification->criado_em
        ]);
    }

    public function findById(string $notificationId): ?NotificationDTO
    {
        $sql = "SELECT * FROM notifications WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $notificationId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return new NotificationDTO(
            id: $result['id'],
            destinatario_id: $result['destinatario_id'],
            remetente_id: $result['remetente_id'],
            tipo: $result['tipo'],
            referencia_id: $result['referencia_id'] ?? null,
            referencia_tipo: $result['referencia_tipo'] ?? null,
            lida: (bool) $result['lida'],
            criado_em: $result['criado_em']
        );
    }

    public function getByUser(string $userId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT *
            FROM notifications
            WHERE destinatario_id = :user_id
            ORDER BY criado_em DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $userId);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(string $notificationId): bool
    {
        $sql = "UPDATE notifications SET lida = 1 WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $notificationId]);
    }

    public function markAllAsRead(string $userId): bool
    {
        $sql = "
            UPDATE notifications
            SET lida = 1
            WHERE destinatario_id = :user_id
        ";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":user_id" => $userId]);
    }

    public function countUnread(string $userId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM notifications
            WHERE destinatario_id = :user_id
            AND lida = 0
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":user_id" => $userId]);

        return (int) $stmt->fetchColumn();
    }
}