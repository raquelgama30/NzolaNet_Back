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
            id, destinatario_id, remetente_id, tipo,
            referencia_id, referencia_tipo, lida,
            agrupada, contagem_agrupada, criado_em
        ) VALUES (
            :id, :destinatario_id, :remetente_id, :tipo,
            :referencia_id, :referencia_tipo, :lida,
            :agrupada, :contagem_agrupada, :criado_em
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
        ":lida" => $notification->lida ? 1 : 0,
        ":agrupada" => $notification->agrupada ? 1 : 0,
        ":contagem_agrupada" => $notification->contagem_agrupada,
        ":criado_em" => $notification->criado_em
    ]);
}

    public function findById(string $notificationId): ?NotificationDTO
    {
        $sql  = "SELECT * FROM notifications WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $notificationId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) return null;

        return $this->mapToDTO($result);
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
        $stmt->bindValue(":limit",   $limit,  PDO::PARAM_INT);
        $stmt->bindValue(":offset",  $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'mapToDTO'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markAsRead(string $notificationId): bool
    {
        $sql  = "UPDATE notifications SET lida = true WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $notificationId]);
    }

    public function markAllAsRead(string $userId): bool
    {
        $sql = "
            UPDATE notifications
            SET lida = true
            WHERE destinatario_id = :user_id
        ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":user_id" => $userId]);
    }

    public function countUnread(string $userId): int
    {
        $sql = "
        SELECT COUNT(*) FROM notifications
        WHERE destinatario_id = :user_id
        AND lida = false
        AND arquivada = false
    ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":user_id" => $userId]);
        return (int) $stmt->fetchColumn();
    }
    public function findSimilar(
        string $destinatarioId,
        string $tipo,
        ?string $referenciaId
    ): ?NotificationDTO {
        $sql = "
        SELECT * FROM notifications
        WHERE destinatario_id = :destinatario_id
        AND tipo = :tipo
        AND referencia_id IS NOT DISTINCT FROM :referencia_id
        AND lida = false
        AND arquivada = false
        ORDER BY criado_em DESC
        LIMIT 1
    ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":destinatario_id" => $destinatarioId,
            ":tipo" => $tipo,
            ":referencia_id" => $referenciaId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToDTO($row) : null;
    }

    public function incrementarContagem(string $notificationId): bool
    {
        $sql = "
        UPDATE notifications 
        SET contagem_agrupada = contagem_agrupada + 1,
            agrupada = true,
            criado_em = NOW()
        WHERE id = :id
    ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $notificationId]);
    }
    // ============================================================
    // MAPPER
    // ============================================================

    private function mapToDTO(array $row): NotificationDTO
{
    return new NotificationDTO(
        id: $row['id'],
        destinatario_id: $row['destinatario_id'],
        remetente_id: $row['remetente_id'],
        tipo: $row['tipo'],
        referencia_id: $row['referencia_id'] ?? null,
        referencia_tipo: $row['referencia_tipo'] ?? null,
        lida: (bool) $row['lida'],
        agrupada: (bool) ($row['agrupada'] ?? false),
        contagem_agrupada: (int) ($row['contagem_agrupada'] ?? 1),
        criado_em: $row['criado_em']
    );
}
}
