<?php

namespace Travoltron\Plaid\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpgradeAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if(app()->environment('testing')) {
            return true;
        }
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
            'products' => 'required|array',
            'token' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'products.required' => 'products is required.',
            'products.array' => 'products must be an array.',
            'token.required' => 'token is required.',
        ];
    }

    public function response(array $errors)
    {
        $errors = collect($errors)->map(function($message) {
            return $message[0];
        });
        return response()->api($errors, 400);
    }
}
