<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;

class SettingsPanel extends Component
{
    public string $site_name;
    public string $site_description;
    public string $super_admin_user_email;
    public string $cc_client_url;
    public bool   $cc_client_lock_url;
    
    protected $rules = [
        'cc_client_url'          => 'url',
        'cc_client_lock_url'     => 'boolean'
    ];

    public function submit() {
        $this->validate();

        $site_name_setting = Setting::firstOrCreate(
            ['key' => 'site_name'],
            ['value' => '']
        );
        $site_name_setting->value = $this->site_name;
        $site_name_setting->save();

        $site_description_setting = Setting::firstOrCreate(
            ['key' => 'site_description'],
            ['value' => '']
        );
        $site_description_setting->value = $this->site_description;
        $site_description_setting->save();

        $cc_client_url_setting = Setting::firstOrCreate(
            ['key' => 'cc_client_url'],
            ['value' => '']
        );
        $cc_client_url_setting->value = $this->cc_client_url;
        $cc_client_url_setting->save();

        $cc_client_lock_url_setting = Setting::firstOrCreate(
            ['key' => 'cc_client_lock_url'],
            ['value' => 0 ]
        );
        $cc_client_lock_url_setting->value = $this->cc_client_lock_url;
        $cc_client_lock_url_setting->save();
    }


    public function mount() {
        $this->settings = Setting::all();
        
        $this->site_name = Setting::firstOrNew(
            ['key' => 'site_name']
        )->value ?? '';

        $this->site_description = Setting::firstOrNew(
            ['key' => 'site_description']
        )->value ?? '';

        $this->cc_client_url = Setting::firstOrNew(
            ['key' => 'cc_client_url']
        )->value ?? '';
       
        $cc_client_lock_setting = Setting::firstOrNew(
            ['key' => 'cc_client_lock_url']
        );
        $this->cc_client_lock_url = $cc_client_lock_setting->value === '1';
    }

    public function getSuperAdminUserEmail()
    {
        $super_admin_user_id = Setting::firstOrNew(
            ['key' => 'super_admin_user_id']
        )->value;
        
        if ( ! $super_admin_user_id ) {
            return null;
        }

        $superAdminUser = User::find( $super_admin_user_id );
        return $superAdminUser->email;
    }

    public function setSuperAdminUserEmail( $value ) {
        $user = User::where( 'email', $value )->first();
        if ( ! $user ) {
            return;
        }

        $setting = Setting::firstOrCreate(
            ['key' => 'super_admin_user_id'],
            ['value' => '']
        );

        $setting->value = $user->id;
        $setting->save();
    }

    public function render()
    {
        return view('livewire.settings-panel');
    }
}
