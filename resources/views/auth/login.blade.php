<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative" x-data="{ show: false }">
                <x-text-input id="password" class="block mt-1 w-full pe-10"
                                x-bind:type="show ? 'text' : 'password'"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <button type="button" x-on:click="show = !show"
                        class="absolute inset-y-0 end-0 mt-1 flex items-center px-3 text-ink-mid hover:text-ink focus:outline-none focus:text-green"
                        x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                    <span x-show="!show"><x-icon name="eye" size="20" /></span>
                    <span x-show="show" x-cloak><x-icon name="eye-off" size="20" /></span>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-cream-deep text-green shadow-sm focus:ring-green" name="remember">
                <span class="ms-2 text-sm text-ink-mid">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-ink-mid hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
