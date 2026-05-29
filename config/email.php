<?php

class EmailConfig
{
    public static function getBrevoApiKey(): string
    {
        return getenv('BREVO_API_KEY') ?: '';
    }

    const FROM_EMAIL = "nzolanet@gmail.com";
    const FROM_NAME  = "Nzolanet";
}