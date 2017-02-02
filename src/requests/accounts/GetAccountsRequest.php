<?php

namespace Travoltron\Plaid\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class GetAccountsRequest extends FormRequest
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
            'token' => 'required',
            'scope' => 'required|in:all,checking,savings,credit,loan,mortgage'
        ];
    }

    public function messages()
    {
        return [
            'token.required' => 'token is required.',
            'scope.required' => 'scope is required.',
            'scope.in' => 'scope nust be one of all, checking, savings, credit, loan, mortgage.',
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
