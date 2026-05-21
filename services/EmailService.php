<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/email.php";

class EmailService
{

    // ============================================
    // ENVIAR EMAIL DE VERIFICAÇÃO
    // ============================================

    public function sendVerificationEmail($email, $nome, $token)
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

            $mail->setFrom(
                EmailConfig::getSmtpUser(),
                EmailConfig::FROM_NAME
            );

            $mail->addAddress($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = "Verifica o teu email — Nzolanet";

            $appUrl = getenv('APP_URL') ?: "https://nzolanet-back.onrender.com";
            $link   = $appUrl . "?route=auth&action=verificarEmail&token=" . $token;

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4F46E5;'>Olá, $nome!</h2>
                    <p>Obrigado por te registares no <strong>Nzolanet</strong>.</p>
                    <p>Clica no botão abaixo para verificar o teu email:</p>
                    <a href='$link' style='
                        background-color: #4F46E5;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 6px;
                        display: inline-block;
                        margin: 16px 0;
                        font-size: 16px;
                    '>
                        Verificar Email
                    </a>
                    <p style='color: #666;'>O link expira em <strong>24 horas</strong>.</p>
                    <p style='color: #666;'>Se não foste tu, ignora este email.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>Nzolanet — A tua rede social</p>
                </div>
            ";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Erro ao enviar email de verificação: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // ENVIAR EMAIL DE RESET DE PASSWORD
    // ============================================

    public function sendPasswordResetEmail($email, $nome, $token)
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

            $mail->setFrom(
                EmailConfig::getSmtpUser(),
                EmailConfig::FROM_NAME
            );

            $mail->addAddress($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = "Recuperação de password — Nzolanet";

            // URL do frontend — usa variável de ambiente em produção
            $frontendUrl = getenv('FRONTEND_URL') ?: "http://localhost:4200";
            $link        = $frontendUrl . "/recuperar-password/" . $token;

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4F46E5;'>Olá, $nome!</h2>
                    <p>Recebemos um pedido para recuperar a tua password no <strong>Nzolanet</strong>.</p>
                    <p>Clica no botão abaixo para definir uma nova password:</p>
                    <a href='$link' style='
                        background-color: #4F46E5;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 6px;
                        display: inline-block;
                        margin: 16px 0;
                        font-size: 16px;
                    '>
                        Recuperar Password
                    </a>
                    <p style='color: #666;'>O link expira em <strong>1 hora</strong>.</p>
                    <p style='color: #666;'>Se não foste tu, ignora este email. A tua password não será alterada.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>Nzolanet — A tua rede social</p>
                </div>
            ";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Erro ao enviar email de reset: " . $e->getMessage());
            return false;
        }
    }
}