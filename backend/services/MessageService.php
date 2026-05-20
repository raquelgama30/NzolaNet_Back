<?php

declare(strict_types=1);

class MessageService extends BaseService implements IMessageService
{
    private IMessageRepository      $messageRepository;
    private IConversationRepository $conversationRepository;
    private INotificationService    $notificationService;
    private IBlockRepository        $blockRepository;  // NOVO

    public function __construct(
        IMessageRepository      $messageRepository,
        IConversationRepository $conversationRepository,
        INotificationService    $notificationService,
        IBlockRepository        $blockRepository  // NOVO
    ) {
        $this->messageRepository      = $messageRepository;
        $this->conversationRepository = $conversationRepository;
        $this->notificationService    = $notificationService;
        $this->blockRepository        = $blockRepository;
    }

    public function sendMessage(string $senderId, MessageDTO $dto): bool
    {
        $conversa = $this->conversationRepository->findById($dto->conversation_id);
        if (!$conversa) throw new Exception("Conversa não encontrada");

        // Determinar destinatário
        $destinatarioId = $conversa->user1_id === $senderId ? $conversa->user2_id : $conversa->user1_id;

        // NOVO: Verificar blocks
        if ($this->blockRepository->isBlocked($destinatarioId, $senderId)) {
            throw new Exception("Não podes enviar mensagem para este utilizador");
        }
        if ($this->blockRepository->isBlocked($senderId, $destinatarioId)) {
            throw new Exception("Desbloqueia este utilizador primeiro");
        }

        $message = new Message(
            id: $this->generateUUID(),
            conversation_id: $dto->conversation_id,
            remetente_id: $senderId,
            conteudo: $dto->conteudo,
            lida: false,
            eliminado: false,
            criado_em: date("Y-m-d H:i:s")
        );

        $created = $this->messageRepository->create($message);

        if ($created) {
            $this->notificationService->create(new NotificationDTO(
                id: "",
                destinatario_id: $destinatarioId,
                remetente_id: $senderId,
                tipo: "mensagem",
                referencia_id: $dto->conversation_id,
                referencia_tipo: "conversation",
                agrupada: false,        // ← NOVO
                contagem_agrupada: 1,
                lida: false,
                criado_em: ""
            ));
        }

        return $created;
    }

    public function getByConversation(string $conversationId, int $page, int $limit): array
    {
        return $this->messageRepository->getByConversation($conversationId, $page, $limit);
    }

    public function delete(string $messageId): bool
    {
        return $this->messageRepository->delete($messageId);
    }
}
