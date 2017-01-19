<?php

namespace Travoltron\Plaid\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchTypeRequest extends FormRequest
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
            'type' => 'required|in:auth,connect,income,info,risk',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'type is required.',
            'type.in' => 'type must be one of auth, connect, income, info, or risk.',
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
