<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;

class SettingsController extends Controller
{
   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Retrieve a settings value.
     */
    public static function get(string $key) : string
    {
        $setting = Setting::firstOrCreate(
            ['key' => $key],
            ['value' => '']
        );

        return $setting->value;
    }

    public function set(string $key, string $value) : void
    {
        $setting = Setting::firstOrCreate(
            ['key' => $key],
            ['value' => '']
        );

        $setting->value = $value;
        $setting->save();
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
