<?php

require_once __DIR__ . "/../config/email.php";

class EmailService
{
    private string $brevoApiKey;

    public function __construct()
    {
        $this->brevoApiKey = EmailConfig::getBrevoApiKey();
    }

    // ============================================
    // ENVIAR EMAIL DE VERIFICAÇÃO
    // ============================================

    public function sendVerificationEmail($email, $nome, $token): bool
    {
        return $this->sendViaBrevo(
            $email,
            $nome,
            $token,
            'verification'
        );
    }

    // ============================================
    // ENVIAR EMAIL DE RESET DE PASSWORD
    // ============================================

    public function sendPasswordResetEmail($email, $nome, $token): bool
    {
        return $this->sendViaBrevo(
            $email,
            $nome,
            $token,
            'reset'
        );
    }

    // ============================================
    // BREVO API
    // ============================================

    private function sendViaBrevo(
        $email,
        $nome,
        $token,
        $type
    ): bool {

        $frontendUrl =
            getenv('FRONTEND_URL')
            ?: "https://nzola-net.vercel.app";

        if ($type === 'verification') {

            $subject =
                "Verifica o teu email — Nzolanet";

            // AGORA VAI PARA O FRONTEND
            $link =
                $frontendUrl .
                "/confirmar-registo?token=" .
                $token;

            $html =
                "<h2>Olá, {$nome}!</h2>
                <p>Clica no botão abaixo para verificar o teu email:</p>
                <p>
                    <a href='{$link}'
                       style='background:#102c26;
                              color:white;
                              padding:10px 18px;
                              text-decoration:none;
                              border-radius:6px;'>
                        Verificar Email
                    </a>
                </p>
                <p>O link expira em 24 horas.</p>";

        } else {

            $link =
                $frontendUrl .
                "/recuperar-password/" .
                $token;

            $subject =
                "Recuperação de password — Nzolanet";

            $html =
                "<h2>Olá, {$nome}!</h2>
                <p>Clica no botão abaixo para recuperar a tua password:</p>
                <p>
                    <a href='{$link}'
                       style='background:#102c26;
                              color:white;
                              padding:10px 18px;
                              text-decoration:none;
                              border-radius:6px;'>
                        Recuperar Password
                    </a>
                </p>
                <p>O link expira em 1 hora.</p>";
        }

        $data = [
            "sender" => [
                "name"  => EmailConfig::FROM_NAME,
                "email" => EmailConfig::FROM_EMAIL
            ],
            "to" => [
                [
                    "email" => $email,
                    "name"  => $nome
                ]
            ],
            "subject" => $subject,
            "htmlContent" => $html
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.brevo.com/v3/smtp/email",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "api-key: " . $this->brevoApiKey,
                "content-type: application/json"
            ]
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        $curlError = curl_error($ch);

        curl_close($ch);

        if ($httpCode == 201 || $httpCode == 200) {

            error_log(
                "Email enviado via Brevo para: " .
                $email
            );

            return true;
        }

        error_log(
            "Erro Brevo HTTP {$httpCode}: "
            . $response .
            " CURL: " .
            $curlError
        );

        return false;
    }
}