<?php

class EmailConfig {

    public static function getSmtpUser(): string {
        return getenv('SMTP_USER') ?: "nzolanet@gmail.com";
    }

    public static function getSmtpPass(): string {
        return getenv('SMTP_PASS') ?: "pnnc pkdl pazb fgai";
    }

    const SMTP_HOST = "smtp.gmail.com";
    const SMTP_PORT = 587;
    const FROM_NAME = "Nzolanet";
}