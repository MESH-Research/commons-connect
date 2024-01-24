<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Account;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $account = Account::find( $id );

        if ( ! empty( $account ) ) {
            return response()->json( $account );
        } else {
            return response()->json( [ 'error' => 'Account not found' ], 404 );
        }
    }

    public function lookup(Request $request)
    {
        $account = Account::where( 'username', $request->acct )->first();

        if ( ! empty( $account ) ) {
            return response()->json( $account );
        } else {
            return response()->json( [ 'error' => 'Account not found' ], 404 );
        }
    }

    public function profile(string $username ) {
        $account = Account::where( 'username', $username )->first();

        if ( empty( $account ) ) {
            return response()->json( [ 'error' => 'Account not found' ], 404 );
        }

        $profile_fields = [
            'username',
            'display_name',
            'note',
            'url',
        ];

        $profile = array_intersect_key( $account->toArray(), array_flip( $profile_fields ) );
        return response()->json( $profile );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
