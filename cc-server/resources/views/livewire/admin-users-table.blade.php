<form wire:submit='submit'>
    <table class="table-auto w-full border">
        <tr>
            <th class="border p-3">Email</th>
            <th class="border p-3">Created At</th>
            <th class="border p-3">Verified</th>
            <th class="border p-3">Role</th>
            <th class="border p-3">Approved?</th>
        </tr>
        @foreach( $users as $i => $user )
        <tr>
            <td class="border p-3">{{ $user->email }}</td>
            <td class="border p-3">{{ $user->created_at }}</td>
            <td class="border p-3">{{ $user->email_verified_at }}</td>
            <td class="border p-3">
                <select 
                    wire:model.live="user_roles.{{ $user->id }}"
                    class="text-black"
                >
                    <option value="">Select a Role</option>
                    @foreach( $roles as $role )
                        <option 
                            value="{{ $role->name }}"
                            @if( $user->hasRole( $role->name ) )
                                selected
                            @endif
                            @if( ! auth()->user()->can( 'make ' . $role->name ) )
                                disabled
                            @endif
                        >
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="border p-3">
                <input type="checkbox" wire:model.live="users.{{ $i }}.approved" class="mr-2" />
                @if( $user->approved )
                    <span class="text-green-500">Approved</span>
                @else
                    <span class="text-red-500">Not Approved</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    <div class="flex flex-row justify-end my-4">
        <button type='submit' class='inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3 w-fit'>Save</button>
    </div>
</form>
