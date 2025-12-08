<?php

namespace App\Services;

interface SmsGateway
{
    /**
     * Send an SMS to the specified phone number.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function send(string $phoneNumber, string $message): bool;
}
