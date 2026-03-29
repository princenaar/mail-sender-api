<?php

namespace App\Models;

use App\Enums\MailStatus;
use Database\Factories\MailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'to', 'cc', 'bcc', 'subject',
    'text_body', 'html_body',
    'status', 'error_message', 'sent_at',
])]
class MailLog extends Model
{
    /** @use HasFactory<MailLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'to'      => 'array',
            'cc'      => 'array',
            'bcc'     => 'array',
            'status'  => MailStatus::class,
            'sent_at' => 'datetime',
        ];
    }
}
