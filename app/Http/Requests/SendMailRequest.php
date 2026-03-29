<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to'                    => ['required', 'array', 'min:1'],
            'to.*'                  => ['required', 'email'],
            'cc'                    => ['nullable', 'array'],
            'cc.*'                  => ['email'],
            'bcc'                   => ['nullable', 'array'],
            'bcc.*'                 => ['email'],
            'subject'               => ['required', 'string', 'max:998'],
            'text_body'             => ['nullable', 'string', 'required_without:html_body'],
            'html_body'             => ['nullable', 'string', 'required_without:text_body'],
            'attachments'           => ['nullable', 'array'],
            'attachments.*.name'    => ['required', 'string'],
            'attachments.*.content' => ['required', 'string'],
            'attachments.*.mime'    => ['required', 'string'],
        ];
    }
}
