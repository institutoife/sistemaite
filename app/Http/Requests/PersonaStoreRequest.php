<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonaStoreRequest extends FormRequest
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
            "apellidop" =>'required|string|max:25',
            "apellidom" => 'nullable|string|max:25',
            "fechanacimiento" =>'required|date',
            // "pais_id" => 'required|integer',
            // "ciudad_id" => 'required|integer',
            "zona_id" => 'required|integer',
            "direccion" => 'required|string|max:120',
            "carnet" => 'nullable|string',
            "expedido" => 'nullable|string',
            "genero" => 'required|string',
            "observacion" =>'required|string|max:400',
            "como_id" =>'required|string|max:30',
            "foto" => \App\Services\ProfilePhotoService::RULES,
            "papel" =>'required|string',
            "telefono" => 'nullable|max:8',
            'interests'=>'required',
            'crm_prospecto_id'=>'nullable|integer|exists:prospectos,id',
            'crm_prospecto_version'=>'required_with:crm_prospecto_id|integer|min:0',
        ];
    }
}
