<?php

namespace App\Http\Requests;

use App\Models\Configuracion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CanjePuntosRequest extends FormRequest
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
        $config = Configuracion::actual();
        $multiplo = $config->obtenerMultiploCanje();

        return [
            'distribuidora_id' => ['required', 'exists:users,id'],
            'puntos_canjear' => [
                'required', 
                'integer', 
                'min:' . $multiplo,
                function ($attribute, $value, $fail) use ($multiplo) {
                    if ($value % $multiplo !== 0) {
                        $fail("Los puntos a canjear deben ser múltiplos de {$multiplo}.");
                    }
                },
            ],
        ];
    }
}
