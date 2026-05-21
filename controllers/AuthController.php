<?php

class AuthController extends BaseController
{
    private UserService          $userService;
    private SessionService       $sessionService;
    private EmailVerificationService $emailVerificationService;
    private PasswordResetService $passwordResetService;

    public function __construct(
        UserService          $userService,
        SessionService       $sessionService,
        EmailVerificationService $emailVerificationService,
        PasswordResetService $passwordResetService
    ) {
        $this->userService              = $userService;
        $this->sessionService           = $sessionService;
        $this->emailVerificationService = $emailVerificationService;
        $this->passwordResetService     = $passwordResetService;
    }

    // ============================================================
    // REGISTO - ASSÍNCRONO (responde primeiro, email depois)
    // ============================================================

    public function register(UserRegisterDTO $dto): void
    {
        try {
            // 1. Registar SEM enviar email
            $user = $this->userService->registerSemEmail($dto);

            // 2. PREPARAR resposta
            $response = json_encode([
                "success" => true,
                "message" => "Conta criada com sucesso. Verifica o teu email.",
                "data"    => $user
            ]);

            // 3. ENVIAR resposta IMEDIATAMENTE
            http_response_code(201);
            header("Content-Type: application/json");
            header("Content-Length: " . strlen($response));
            echo $response;

            // 4. FECHAR ligação com cliente
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }

            // 5. ENVIAR EMAIL DEPOIS (cliente já recebeu resposta)
            $this->userService->enviarEmailVerificacao(
                $dto->email,
                $dto->nome,
                $user->id
            );

        } catch (Exception $e) {
            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    // ============================================================
    // LOGIN
    // ============================================================

    public function login(UserLoginDTO $dto): void
    {
        try {
            $result = $this->userService->login($dto);

            $this->sessionService->create(
                $result["user"]->id,
                $result["hash"]
            );

            $this->json([
                "success" => true,
                "message" => "Login efetuado com sucesso",
                "token"   => $result["token"],
                "user"    => $result["user"]
            ]);

        } catch (Exception $e) {
            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 401);
        }
    }

    // ============================================================
    // LOGOUT
    // ============================================================

    public function logout(string $token): void
    {
        $hash = hash("sha256", $token);
        $this->sessionService->deleteByHash($hash);

        $this->json([
            "success" => true,
            "message" => "Logout efetuado com sucesso"
        ]);
    }

    // ============================================================
    // VERIFICAR EMAIL
    // ============================================================

    public function verifyEmail(string $token): void
    {
        try {
            $this->emailVerificationService->verify($token);

            $this->json([
                "success" => true,
                "message" => "Email verificado com sucesso"
            ]);

        } catch (Exception $e) {
            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    // ============================================================
    // ESQUECEU PASSWORD - ASSÍNCRONO
    // ============================================================

    public function forgotPassword(ForgotPasswordDTO $dto): void
    {
        try {
            // 1. Gerar token e buscar dados do usuário
            $result = $this->passwordResetService->gerarTokenResetPassword($dto->email);

            // 2. PREPARAR resposta (sempre genérica por segurança)
            $response = json_encode([
                "success" => true,
                "message" => "Se o email existir, receberás instruções para redefinir a password."
            ]);

            // 3. ENVIAR resposta IMEDIATAMENTE
            http_response_code(200);
            header("Content-Type: application/json");
            header("Content-Length: " . strlen($response));
            echo $response;

            // 4. FECHAR ligação com cliente
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }

            // 5. ENVIAR EMAIL DEPOIS (se o email existir)
            if ($result) {
                $this->passwordResetService->enviarEmailResetPassword(
                    $dto->email,
                    $result['nome'],
                    $result['token']
                );
            }

        } catch (Exception $e) {
            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    // ============================================================
    // REDEFINIR PASSWORD
    // ============================================================

    public function resetPassword(PasswordResetDTO $dto): void
    {
        try {
            $this->passwordResetService->resetPassword($dto);

            $this->json([
                "success" => true,
                "message" => "Password redefinida com sucesso"
            ]);

        } catch (Exception $e) {
            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }
}