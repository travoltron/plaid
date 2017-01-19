<?php

namespace Travoltron\Plaid\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchNameRequest extends FormRequest
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
            'query' => 'required|min:3',
        ];
    }

    public function messages()
    {
        return [
            'query.required' => 'query is required.',
            'query.min' => 'query must be at least 3 characters.',
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
