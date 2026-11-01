<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UserUpdateRequest extends FormRequest
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
            return ['name'=>'required|string|max:64','email'=>'required|email|max:64','password'=>'prohibited','foto'=>\App\Services\ProfilePhotoService::RULES];
        }
        $user=$this->route('user');
        return [
            'name'=>'required|max:64|unique:users,name,'.$user->id,
            'email'=>'required|max:64|unique:users,email,'.$user->id,
            'password'=>'nullable',
            'foto'=>\App\Services\ProfilePhotoService::RULES,
        ];
    }
}
