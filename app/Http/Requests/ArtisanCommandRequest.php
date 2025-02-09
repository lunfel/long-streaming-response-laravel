<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtisanCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'command' => 'required'
        ];
    }

    public function getCommand(): string
    {
        return $this->get('command');
    }
}
