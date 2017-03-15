<?php

namespace Travoltron\Plaid\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
            'accountNumber' => 'required',
            'routingNumber' => 'required|digits:9',
            'accountId' => 'required|exists:plaid_accounts'
        ];
    }

    public function messages()
    {
        return [
            'accountNumber.required' => 'accountNumber is required.',
            'routingNumber.required' => 'routingNumber is required.',
            'routingNumber.digits' => 'routingNumber must be 9 digits.',
            'accountId.required' => 'accountId is required.',
            'accountId.exists' => 'accountId must exist in database. No record of this accountId.',
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
