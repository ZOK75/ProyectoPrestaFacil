<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SolicitarConciliacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->esCajero();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'prestamo_id' => ['required', 'exists:prestamos,id'],
            'monto_original' => ['required', 'numeric', 'min:0'],
            'monto_corregido' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'max:500'],
            'evidencia' => ['nullable', 'image', 'max:5120'], // Max 5MB
        ];
    }
}
