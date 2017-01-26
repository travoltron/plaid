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
        $banks = collect(Plaid::search($request->input('query')));
        if(!$banks->isEmpty()) {
            $results = $banks->map(function($bank) {
                return [
                    'name' => $bank['name'],
                    'id' => $bank['id'],
                    'logo' => $bank['logo'],
                    'fields' => $bank['fields']
                ];
            });
            return response()->json([
                'status' => 200,
                'banks' => $results,
            ], 200);
        }
        return response()->json([
            'status' => 404,
            'data' => [],
            'errors' => 'No results found'], 404);
    }

    public function searchId(SearchIdRequest $request)
    {
        $bank = collect(Plaid::searchId($request->input('id')));
        dd($bank);
    }

    public function searchProduct(SearchTypeRequest $request)
    {
        $results = Plaid::searchType($request->input('type'));
        dd($results);
    }
}
