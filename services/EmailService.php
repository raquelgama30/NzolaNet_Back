<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/email.php";

class EmailService
{
    private string $resendApiKey;
    private bool $useResend;

    public function __construct()
    {
        $this->resendApiKey = getenv('RESEND_API_KEY') ?: '';
        // Se tiver RESEND_API_KEY no Render, usa Resend. Se não, usa SMTP local.
        $this->useResend = !empty($this->resendApiKey);
    }

    // ============================================
    // ENVIAR EMAIL DE VERIFICAÇÃO
    // ============================================

    public function sendVerificationEmail($email, $nome, $token)
    {
        // Se estiver no Render (tem RESEND_API_KEY), usa API HTTP
        if ($this->useResend) {
            return $this->sendViaResend($email, $nome, $token, 'verification');
        }

        // Se não, usa PHPMailer SMTP local
        return $this->sendViaSMTP($email, $nome, $token, 'verification');
    }

    // ============================================
    // ENVIAR EMAIL DE RESET DE PASSWORD
    // ============================================

    public function sendPasswordResetEmail($email, $nome, $token)
    {
        if ($this->useResend) {
            return $this->sendViaResend($email, $nome, $token, 'reset');
        }

        return $this->sendViaSMTP($email, $nome, $token, 'reset');
    }

    // ============================================
    // RESEND API (HTTP - funciona no Render)
    // ============================================

    private function sendViaResend($email, $nome, $token, $type): bool
    {
        $appUrl = getenv('APP_URL') ?: "https://nzolanet-back.onrender.com";

        if ($type === 'verification') {
            $subject = "Verifica o teu email — Nzolanet";
            $link = $appUrl . "?route=auth&action=verificarEmail&token=" . $token;
            $html = "<h2>Olá, {$nome}!</h2><p>Clica para verificar: <a href='{$link}'>Verificar Email</a></p><p>O link expira em 24 horas.</p>";
        } else {
            $frontendUrl = getenv('FRONTEND_URL') ?: "http://localhost:4200";
            $link = $frontendUrl . "/recuperar-password/" . $token;
            $subject = "Recuperação de password — Nzolanet";
            $html = "<h2>Olá, {$nome}!</h2><p>Clica para recuperar: <a href='{$link}'>Recuperar Password</a></p><p>O link expira em 1 hora.</p>";
        }

        $data = [
            'from' => 'onboarding@resend.dev',
            'to' => [$email],
            'subject' => $subject,
            'html' => $html
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->resendApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            error_log("Email enviado via Resend para: " . $email);
            return true;
        } else {
            error_log("Erro Resend HTTP " . $httpCode . ": " . $response);
            return false;
        }
    }

    // ============================================
    // SMTP LOCAL (PHPMailer - funciona no XAMPP)
    // ============================================

    private function sendViaSMTP($email, $nome, $token, $type): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = EmailConfig::getSmtpUser();
            $mail->Password   = EmailConfig::getSmtpPass();
            $mail->SMTPSecure = "tls";
            $mail->Port       = EmailConfig::SMTP_PORT;
            $mail->CharSet    = "UTF-8";
            $mail->Timeout    = 10;

            $mail->setFrom(EmailConfig::getSmtpUser(), EmailConfig::FROM_NAME);
            $mail->addAddress($email, $nome);
            $mail->isHTML(true);

            $appUrl = getenv('APP_URL') ?: "https://nzolanet-back.onrender.com";

            if ($type === 'verification') {
                $mail->Subject = "Verifica o teu email — Nzolanet";
                $link = $appUrl . "?route=auth&action=verificarEmail&token=" . $token;
                $mail->Body = "<h2>Olá, {$nome}!</h2><p>Clica para verificar: <a href='{$link}'>Verificar Email</a></p>";
            } else {
                $frontendUrl = getenv('FRONTEND_URL') ?: "http://localhost:4200";
                $link = $frontendUrl . "/recuperar-password/" . $token;
                $mail->Subject = "Recuperação de password — Nzolanet";
                $mail->Body = "<h2>Olá, {$nome}!</h2><p>Clica para recuperar: <a href='{$link}'>Recuperar Password</a></p>";
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Erro SMTP: " . $e->getMessage());
            return false;
        }
    }
}