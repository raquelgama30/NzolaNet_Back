<?php

declare(strict_types=1);

class NotificationService extends BaseService implements INotificationService
{
    private INotificationRepository $notificationRepository;

    public function __construct(
        INotificationRepository $notificationRepository
    ) {
        $this->notificationRepository = $notificationRepository;
    }

   public function create(NotificationDTO $dto): bool
{
    // Verificar se existe notificação similar não lida
    $similar = $this->notificationRepository->findSimilar(
        $dto->destinatario_id,
        $dto->tipo,
        $dto->referencia_id
    );

    if ($similar) {
        // Agrupar: incrementar contagem em vez de criar nova
        return $this->notificationRepository->incrementarContagem($similar->id);
    }

    // Criar nova notificação
    $notification = new Notification(
        id: $this->generateUUID(),
        destinatario_id: $dto->destinatario_id,
        remetente_id: $dto->remetente_id,
        tipo: $dto->tipo,
        referencia_id: $dto->referencia_id,
        referencia_tipo: $dto->referencia_tipo,
        lida: false,
        agrupada: false,
        contagem_agrupada: 1,
        criado_em: date("Y-m-d H:i:s")
    );

    return $this->notificationRepository->create($notification);
}
    public function getByUser(
        string $userId,
        int $page,
        int $limit
    ): array {

        return $this->notificationRepository->getByUser(
            $userId,
            $page,
            $limit
        );
    }
    public function countUnread(string $userId): int
    {
        return $this->notificationRepository->countUnread($userId);
    }

    public function markAsRead(
        string $notificationId
    ): bool {

        return $this->notificationRepository->markAsRead(
            $notificationId
        );
    }

    public function markAllAsRead(
        string $userId
    ): bool {

        return $this->notificationRepository->markAllAsRead(
            $userId
        );
    }
}
