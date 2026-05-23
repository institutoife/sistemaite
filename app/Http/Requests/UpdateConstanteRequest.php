<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateConstanteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cuenta' => 'required|string|max:100',
            'plataforma' => 'required|string|max:100',
            'clave' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:1000',
            'constante' => 'required|string|max:25',
            'valor' => 'required|string|max:20',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'constante' => Str::limit($this->input('cuenta', ''), 25, ''),
            'valor' => Str::limit($this->input('clave', ''), 20, ''),
        ]);
    }
}

            
