<?php

namespace App\Support;

class MailFailureReason
{
    public static function fromMessage(?string $message): ?string
    {
        if (blank($message)) {
            return null;
        }

        $message = mb_strtolower($message);

        if (str_contains($message, '5.2.2') || preg_match('/mailbox\s+(?:is\s+)?full|over\s+quota|quota\s+exceeded/', $message)) {
            return 'Boîte de réception pleine (quota dépassé).';
        }

        if (str_contains($message, '5.1.1') || preg_match('/user unknown|unknown user|no such user|recipient not found|address does not exist|address not found/', $message)) {
            return 'Adresse du destinataire inexistante.';
        }

        return null;
    }
}
