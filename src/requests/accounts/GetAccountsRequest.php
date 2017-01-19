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
        return response()->json([
            'status' => 400,
            'data' => [],
            'errors' => collect($errors)->flatten()->toArray()
        ], 400);
    }
}
