<?php

class PasswordResetService
{
    private IPasswordResetRepository $passwordResetRepository;
    private IUserRepository          $userRepository;
    private EmailService             $emailService;

    public function __construct(
        IPasswordResetRepository $passwordResetRepository,
        IUserRepository          $userRepository,
        EmailService             $emailService
    ) {
        $this->passwordResetRepository = $passwordResetRepository;
        $this->userRepository          = $userRepository;
        $this->emailService            = $emailService;
    }

    // ============================================================
    // GERAR TOKEN RESET PASSWORD (sem enviar email)
    // ============================================================

    public function gerarTokenResetPassword(string $email): ?array
    {
        $user = $this->userRepository->findByEmail($email);

        // Não revelar se o email existe (segurança)
        if (!$user) {
            return null;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash("sha256", $plainToken);

        $this->passwordResetRepository->create(new PasswordResetToken(
            id:         $this->generateUUID(),
            user_id:    $user->id,
            token_hash: $tokenHash,
            expira_em:  date("Y-m-d H:i:s", strtotime("+1 hour")),
            usado:      false,
            criado_em:  date("Y-m-d H:i:s")
        ));

        return [
            'token' => $plainToken,
            'nome'  => $user->nome
        ];
    }

    // ============================================================
    // ENVIAR EMAIL RESET PASSWORD (depois de responder ao cliente)
    // ============================================================

    public function enviarEmailResetPassword(
        string $email,
        string $nome,
        string $token
    ): void {
        $this->emailService->sendPasswordResetEmail(
            $email,
            $nome,
            $token
        );
    }

    // ============================================================
    // REDEFINIR PASSWORD (verificar token e atualizar)
    // ============================================================

    public function resetPassword(PasswordResetDTO $dto): void
    {
        $tokenHash = hash("sha256", $dto->token);

        $resetToken = $this->passwordResetRepository->findByToken($tokenHash);

        if (!$resetToken || $resetToken->usado) {
            throw new Exception("Token inválido ou já utilizado");
        }

        if (strtotime($resetToken->expira_em) < time()) {
            throw new Exception("Token expirado");
        }

        $user = $this->userRepository->findById($resetToken->user_id);

        if (!$user) {
            throw new Exception("Utilizador não encontrado");
        }

        // Atualizar password
        $this->userRepository->updatePassword(
            $user->id,
            password_hash($dto->password, PASSWORD_DEFAULT)
        );

        // Marcar token como usado
        $this->passwordResetRepository->markAsUsed($tokenHash);
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}