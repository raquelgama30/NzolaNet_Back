<?php

declare(strict_types=1);

class FollowService extends BaseService implements IFollowService
{
    private IFollowRepository    $followRepository;
    private IUserRepository      $userRepository;
    private INotificationService $notificationService;
    private IBlockRepository     $blockRepository;  // NOVO

    public function __construct(
        IFollowRepository    $followRepository,
        IUserRepository      $userRepository,
        INotificationService $notificationService,
        IBlockRepository     $blockRepository  // NOVO
    ) {
        $this->followRepository    = $followRepository;
        $this->userRepository      = $userRepository;
        $this->notificationService = $notificationService;
        $this->blockRepository     = $blockRepository;
    }

    public function follow(string $seguidorId, string $seguidoId): array
    {
        // Não pode seguir a si próprio
        if ($seguidorId === $seguidoId) {
            return ["success" => false, "message" => "Não podes seguir-te a ti próprio"];
        }

        // NOVO: Verificar blocks
        if ($this->blockRepository->isBlocked($seguidoId, $seguidorId)) {
            return ["success" => false, "message" => "Não podes seguir este utilizador"];
        }
        if ($this->blockRepository->isBlocked($seguidorId, $seguidoId)) {
            return ["success" => false, "message" => "Desbloqueia este utilizador primeiro"];
        }

        // Verificar se já segue
        $existente = $this->followRepository->isFollowing($seguidorId, $seguidoId);
        if ($existente) {
            if ($existente->status === 'pendente') {
                return ["success" => false, "message" => "Já enviaste um pedido"];
            }
            return ["success" => false, "message" => "Já segues este utilizador"];
        }

        // Verificar privacidade
        $userASeguir = $this->userRepository->findById($seguidoId);
        if (!$userASeguir) {
            return ["success" => false, "message" => "Utilizador não encontrado"];
        }

        $status = $userASeguir->privacidade === 'privado' ? 'pendente' : 'aceite';

        $follow = new Follow(
            id: $this->generateUUID(),
            seguidor_id: $seguidorId,
            seguido_id: $seguidoId,
            status: $status,
            criado_em: date("Y-m-d H:i:s")
        );

        $created = $this->followRepository->follow($follow);
        if (!$created) {
            return ["success" => false, "message" => "Erro ao seguir"];
        }

        // Enviar notificação
        if ($status === 'aceite') {
            $this->notificationService->create(new NotificationDTO(
                id: "",
                destinatario_id: $seguidoId,
                remetente_id: $seguidorId,
                tipo: "seguidor",
                referencia_id: null,
                referencia_tipo: null,
                lida: false,
                agrupada: false,        // ← NOVO
                contagem_agrupada: 1,
                criado_em: ""
            ));
            return ["success" => true, "message" => "Agora segues este utilizador", "status" => "aceite"];
        } else {
            $this->notificationService->create(new NotificationDTO(
                id: "",
                destinatario_id: $seguidoId,
                remetente_id: $seguidorId,
                tipo: "pedido_follow",
                referencia_id: null,
                referencia_tipo: null,
                lida: false,
                agrupada: false,        // ← NOVO
                contagem_agrupada: 1,
                criado_em: ""
            ));
            return ["success" => true, "message" => "Pedido enviado. Aguarda aprovação.", "status" => "pendente"];
        }
    }

    public function unfollow(string $seguidorId, string $seguidoId): bool
    {
        return $this->followRepository->unfollow($seguidorId, $seguidoId);
    }

    public function isFollowing(string $seguidorId, string $seguidoId): bool
    {
        $follow = $this->followRepository->isFollowing($seguidorId, $seguidoId);
        return $follow !== null && $follow->status === 'aceite';
    }

    public function getFollowers(string $userId): array
    {
        return $this->followRepository->getFollowers($userId);
    }

    public function getFollowing(string $userId): array
    {
        return $this->followRepository->getFollowing($userId);
    }

    // CORRIGIDO: Agora notifica o pedinte
    public function aceitarFollow(string $seguidorId, string $seguidoId): bool
    {
        $result = $this->followRepository->updateStatus($seguidorId, $seguidoId, 'aceite');

        if ($result) {
            // NOVO: Notificar o seguidor que o pedido foi aceite
            $this->notificationService->create(new NotificationDTO(
                id: "",
                destinatario_id: $seguidorId,
                remetente_id: $seguidoId,
                tipo: "seguidor",
                referencia_id: null,
                referencia_tipo: null,
                lida: false,
                agrupada: false,        // ← NOVO
                contagem_agrupada: 1,
                criado_em: ""
            ));
        }

        return $result;
    }

    public function rejeitarFollow(string $seguidorId, string $seguidoId): bool
    {
        return $this->followRepository->unfollow($seguidorId, $seguidoId);
    }

    public function getPedidosPendentes(string $userId): array
    {
        return $this->followRepository->getPedidosPendentes($userId);
    }
}
