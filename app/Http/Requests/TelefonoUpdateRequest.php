<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TelefonoUpdateRequest extends FormRequest
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
            'nombre' =>'required',
            'apellidop' => 'required',
            'apellidom'=>'nullable',
            'telefono'=>'nullable|max:10',
            'parentesco'=>'required',
            'genero'=>'required',
            'fechanacimiento' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=4000,max_height=4000',
        ];
    }
}
