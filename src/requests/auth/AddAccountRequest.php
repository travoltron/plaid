<?php

namespace Travoltron\Plaid\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AddAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if(!$this->header('uuid') || !\App\Models\User::uuid($this->header('uuid'))) {
            return false;
        }
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
            'username' => 'required',
            'password' => 'required',
            'pin' => 'required_if:type,usaa',
            'type' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'username is required.',
            'password.required' => 'password is required.',
            'type.required' => 'type is required.',
        ];
    }

    public function response(array $errors)
    {
        return response()->json([
            'status' => 400,
            'data' => [],
            'errors' => collect($errors)->flatten()->toArray()
        ], 400);
    }
}
