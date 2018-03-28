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
        // Temporary Fix
        if(strtolower($request->input('query')) == 'capital one 360') {
            $request->merge(['query' => 'Capital One']);
        }
        // End temp fix
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

    public function routingNumber(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'routingNumber' => 'required|digits:9'
        ]);
        if ($validator->fails()) {
            return response()->api($validator->errors(), 400);
        }
        $routingName = json_decode((new \GuzzleHttp\Client)->request('GET', 'https://www.routingnumbers.info/api/name.json', [
            'decode_content' => true,
            'query' => [
                'rn' => $request->input('routingNumber')
            ]
        ])->getBody()->getContents(), true);
        if($routingName['code'] === 404) {
            return response()->api(['invalidRoutingNumber' => 'The routing number does not exist. Check again.'], 404);
        }
        return response()->api(['bankName' => $routingName['name']], 200);

    }
}
