<?php

declare(strict_types=1);

class UserService extends BaseService implements IUserService
{

    private IUserRepository              $userRepository;
    private IEmailVerificationRepository $emailVerificationRepository;
    private EmailService                 $emailService;
    private IPostService                 $postService;        // NOVO
    private IPostRepository              $postRepository;     // NOVO
    private IFollowRepository            $followRepository;    // NOVO
    private IBlockRepository             $blockRepository;     // NOVO
    private IConversationRepository      $conversationRepository; // NOVO
    private IMessageRepository           $messageRepository;   // NOVO
    private ISessionRepository           $sessionRepository;    // NOVO

    public function __construct(
        IUserRepository              $userRepository,
        IEmailVerificationRepository $emailVerificationRepository,
        EmailService                 $emailService,
        IPostService                 $postService,
        IPostRepository              $postRepository,
        IFollowRepository            $followRepository,
        IBlockRepository             $blockRepository,
        IConversationRepository      $conversationRepository,
        IMessageRepository           $messageRepository,
        ISessionRepository           $sessionRepository
    ) {
        $this->userRepository              = $userRepository;
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->emailService                = $emailService;
        $this->postService                 = $postService;
        $this->postRepository              = $postRepository;
        $this->followRepository            = $followRepository;
        $this->blockRepository             = $blockRepository;
        $this->conversationRepository      = $conversationRepository;
        $this->messageRepository           = $messageRepository;
        $this->sessionRepository           = $sessionRepository;
    }

    // ============================================================
    // REGISTO
    // ============================================================

    public function register(UserRegisterDTO $dto): UserDTO
    {
        $start = microtime(true);

        if ($this->userRepository->findByEmail($dto->email)) {
            throw new Exception("Este email já está registado");
        }
        error_log("find email: " . round(microtime(true) - $start, 2));

        if ($this->userRepository->findByUsername($dto->username)) {
            throw new Exception("Este username já está em uso");
        }
        error_log("find username: " . round(microtime(true) - $start, 2));

        $hash = password_hash($dto->password, PASSWORD_DEFAULT);
        error_log("hash: " . round(microtime(true) - $start, 2));

        $user = new User(
            id: $this->generateUUID(),
            nome: $dto->nome,
            username: $dto->username,
            email: $dto->email,
            password_hash: $hash,
            foto_perfil: null,
            foto_capa: null,
            bio: null,
            data_nascimento: $dto->data_nascimento,
            genero: $dto->genero,
            privacidade: "publico",
            is_admin: false,
            is_active: true,
            email_verificado_em: null,
            ultimo_acesso_em: null,
            criado_em: date("Y-m-d H:i:s"),
            atualizado_em: date("Y-m-d H:i:s")
        );

        $created = $this->userRepository->create($user);
        error_log("insert user: " . round(microtime(true) - $start, 2));

        if (!$created) {
            throw new Exception("Erro ao criar utilizador");
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash("sha256", $plainToken);

        $emailToken = new EmailVerificationToken(
            id: $this->generateUUID(),
            user_id: $user->id,
            token_hash: $tokenHash,
            expira_em: date("Y-m-d H:i:s", strtotime("+7 days")),
            criado_em: date("Y-m-d H:i:s")
        );

        $this->emailVerificationRepository->create($emailToken);
        error_log("insert token: " . round(microtime(true) - $start, 2));

        // Guardar variáveis para usar no shutdown
        $emailService  = $this->emailService;
        $emailAddr     = $dto->email;
        $emailNome     = $dto->nome;
        $emailPlain    = $plainToken;

        // Enviar email DEPOIS de responder ao cliente
        // O utilizador não espera pelo envio
        register_shutdown_function(
            function () use ($emailService, $emailAddr, $emailNome, $emailPlain) {
                $emailService->sendVerificationEmail(
                    $emailAddr,
                    $emailNome,
                    $emailPlain
                );
            }
        );

        error_log("email agendado: " . round(microtime(true) - $start, 2));
        error_log("TOTAL: " . round(microtime(true) - $start, 2));

        return $this->userRepository->findById($user->id);
    }
    // ============================================================
    // LOGIN
    // ============================================================
    public function login(UserLoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user) {
            throw new Exception("Credenciais inválidas");
        }

        // Verificar se a conta está activa
        if (!$user->is_active) {
            throw new Exception("Conta desativada");
        }

        // Verificar se o email foi confirmado
        if (!$user->email_verificado_em) {
            throw new Exception("Email não verificado. Verifica o teu email antes de fazer login.");
        }

        $userWithHash = $this->userRepository->findByEmailWithHash($dto->email);

        if (!$userWithHash) {
            throw new Exception("Credenciais inválidas");
        }

        if (!password_verify($dto->password, $userWithHash->password_hash)) {
            throw new Exception("Credenciais inválidas");
        }

        $this->userRepository->updateUltimoAcesso($user->id);

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash("sha256", $plainToken);

        return [
            "user"  => $user,
            "token" => $plainToken,
            "hash"  => $tokenHash
        ];
    }

    // ============================================================
    // GETTERS
    // ============================================================

    public function getById(string $id): ?UserDTO
    {
        return $this->userRepository->findById($id);
    }

    // ============================================================
    // ATUALIZAR PERFIL
    // ============================================================

    public function updateProfile(string $id, UpdateUserDTO $dto): bool
    {
        $user = new User(
            id: $id,
            nome: $dto->nome         ?? "",
            username: "",
            email: "",
            password_hash: "",
            foto_perfil: $dto->foto_perfil  ?? null,
            foto_capa: $dto->foto_capa    ?? null,
            bio: $dto->bio          ?? null,
            data_nascimento: null,
            genero: "",
            privacidade: $dto->privacidade  ?? "publico",
            is_admin: false,
            is_active: true,
            email_verificado_em: null,
            ultimo_acesso_em: null,
            criado_em: "",
            atualizado_em: date("Y-m-d H:i:s")
        );

        return $this->userRepository->updateProfile($id, $user);
    }

    // ============================================================
    // ALTERAR PASSWORD (verifica a atual primeiro)
    // ============================================================

    public function alterarPassword(
        string $id,
        string $passwordAtual,
        string $passwordNova
    ): bool {
        // Buscar o user com o hash para verificar
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return false;
        }

        $userWithHash = $this->userRepository->findByEmailWithHash($user->email);

        if (!$userWithHash) {
            return false;
        }

        // Verificar se a password atual está correta
        if (!password_verify($passwordAtual, $userWithHash->password_hash)) {
            return false;
        }

        return $this->userRepository->updatePassword(
            $id,
            password_hash($passwordNova, PASSWORD_DEFAULT)
        );
    }

    // ============================================================
    // ATUALIZAR PASSWORD DIRETA (usado pelo reset de password)
    // ============================================================

    public function updatePassword(string $id, string $password): bool
    {
        return $this->userRepository->updatePassword(
            $id,
            password_hash($password, PASSWORD_DEFAULT)
        );
    }

    // ============================================================
    // PESQUISAR
    // ============================================================

    public function pesquisar(string $query): array
    {
        return $this->userRepository->searchUsers($query);
    }
    public function removerFotoPerfil(string $id): bool
    {
        // Buscar o user para saber o URL atual
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return false;
        }

        // Se tem foto, apagar o ficheiro físico do servidor
        if ($user->foto_perfil) {
            $this->apagarFicheiro($user->foto_perfil);
        }

        // Colocar NULL na base de dados
        return $this->userRepository->removerFotoPerfil($id);
    }

    public function removerFotoCapa(string $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return false;
        }

        if ($user->foto_capa) {
            $this->apagarFicheiro($user->foto_capa);
        }

        return $this->userRepository->removerFotoCapa($id);
    }

    public function deleteUser(string $id): bool
    {
        $user = $this->userRepository->findById($id);
        if (!$user) return false;

        // 1. Apagar ficheiros de perfil/capa
        if ($user->foto_perfil) $this->apagarFicheiro($user->foto_perfil);
        if ($user->foto_capa) $this->apagarFicheiro($user->foto_capa);

        // 2. Eliminar todos os posts (com cascata: media, bazes, comentários, shares)
        $posts = $this->postRepository->getFeedByUser($id, 1, 9999);
        foreach ($posts as $post) {
            $this->postService->delete($post->id, $id);
        }

        // 3. Eliminar follows
        $this->followRepository->deleteAllByUserId($id);

        // 4. Eliminar blocks
        $this->blockRepository->deleteAllByUserId($id);

        // 5. Eliminar conversas (mensagens apagam em cascata)
        $conversas = $this->conversationRepository->getByUser($id);
        foreach ($conversas as $conversa) {
            $this->conversationRepository->delete($conversa->id);
        }

        // 6. Terminar sessões
        $this->sessionRepository->deleteAllByUserId($id);

        // 7. Desativar
        return $this->userRepository->deactivate($id);
    }

    private function apagarFicheiro(string $url): void
    {
        $baseUrl  = "http://localhost:8081/NzolaNet/backend/";
        $caminho  = str_replace($baseUrl, "", $url);
        $raiz     = dirname(__DIR__);
        $ficheiro = $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $caminho);

        if (file_exists($ficheiro)) {
            unlink($ficheiro);
        }
    }

    

    // ============================================================
    // ADMIN
    // ============================================================

    public function listarTodos(): array
    {
        return $this->userRepository->getAllUsers();
    }

    public function ativar(string $id): bool
    {
        return $this->userRepository->activateUser($id);
    }
}
