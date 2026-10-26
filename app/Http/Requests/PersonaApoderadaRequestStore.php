<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonaApoderadaRequestStore extends FormRequest
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
            "nombre" => 'required|max:50|unique_with:personas,apellidop,apellidom',
            "apellidop" => 'required|string|max:25',
            "apellidom" => 'required|string|max:25',
            "genero" => 'required|string',
            "telefono" => 'nullable|max:8',
            "parentesco"=>'required',
            'fechanacimiento' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=4000,max_height=4000',
        ];
    }
}
