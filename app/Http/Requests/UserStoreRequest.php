<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
        if (app(\App\Services\AccountSecurity::class)->ready()) {
            return ['name'=>'required|string|max:64','email'=>'required|email|max:64','foto'=>\App\Services\ProfilePhotoService::RULES];
        }
        return [

            'name' => 'required|string|max:64|unique:users',
            'email' => 'required|string|email|max:64|unique:users',
            'foto' => \App\Services\ProfilePhotoService::RULES,
            'password' => [
                'required', 'confirmed', Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],

        ];
    }
}
