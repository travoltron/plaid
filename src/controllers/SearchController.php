<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Search\SearchNameRequest;
use Travoltron\Plaid\Requests\Search\SearchTypeRequest;

class SearchController extends BaseController
{
    public function searchName(SearchNameRequest $request)
    {
        $banks = Plaid::search($request->input('query'));
        if(!collect($banks)->isEmpty()) {
            return response()->json([
                'status' => 200,
                'data' => $banks,
                'errors' => []
            ], 200);
        }
        return response()->json([
            'status' => 404,
            'data' => [],
            'errors' => 'No results found'], 404);
    }

    public function searchProduct(SearchTypeRequest $request)
    {
        $results = Plaid::searchType($request->input('type'));
        dd($results);
    }
}
