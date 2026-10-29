<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestStoreSoloContacto extends FormRequest
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
            "nombre" => 'required|max:50|unique_with:personas,apellidop',
            "apellidop" =>'required|string|max:25',
            "telefono" => 'nullable|max:8',
            "como_id" =>'required',
            'crm_prospecto_id'=>'nullable|integer|exists:prospectos,id',
            'crm_prospecto_version'=>'required_with:crm_prospecto_id|integer|min:0',
            
            "observacion" =>'required|string|max:250',
        ];
    }
}
