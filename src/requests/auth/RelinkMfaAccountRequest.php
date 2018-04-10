<?php

namespace Travoltron\Plaid\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MfaAccountRequest extends FormRequest
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
            'token' => 'required',
            'mfaCode' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'mfaCode.required' => 'mfaCode is required.',
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

    public function forbiddenResponse()
    {
        return response()->api(['uuid' => 'Invalid or missing UUID.'], 403);
    }
}
