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
        $user=$this->route('user');
        return [
            'name'=>'required|max:64|unique:users,name,'.$user->id,
            'email'=>'required|max:64|unique:users,email,'.$user->id,
            'password'=>'nullable',
            'foto'=>\App\Services\ProfilePhotoService::RULES,
        ];
    }
}
