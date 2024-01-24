<form wire:submit='submit' class='flex flex-col gap-5'>
    <div>
        <label for='site_name' class='block'>Site Name</label>
        <input type='text' wire:model.live='site_name'  class='block w-96 text-black'/>
        @error('site_name')
            <span class='text-red-500'>{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label for='site_description' class='block'>Site Description</label>
        <input type='text' wire:model.live='site_description' class='block w-96 text-black' />
        @error('site_description')
            <span class='text-red-500'>{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label for='cc_client_url' class='block'>CC Client URL</label>
        <div class='flex flex-row gap-2 items-baseline'>
            <input type='text' wire:model.live='cc_client_url' class='block w-96 text-black' />
            @if( $cc_client_url )
                <a href='{{ $cc_client_url }}' target='_blank' class='underline after:content-["_↗"]'>Open Client Site</a>
            @endif
        </div>
        @error( 'cc_client_url' )
            <span class='text-red-500'>{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label for='cc_client_lock_url' class='block'>Restrict CC Client Connections to URL?</label>
        <input type='checkbox' wire:model.live='cc_client_lock_url' class='block text-black' />
        @error( 'cc_client_lock_url' )
            <span class='text-red-500'>{{ $message }}</span>
        @enderror
    </div>
    <button type='submit' class='inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3 w-fit'>Save</button>
</form>

