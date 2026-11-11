<?php

/*
|--------------------------------------------------------------------------
| Livewire
|--------------------------------------------------------------------------
|
| Only overrides; everything else comes from Livewire's own config.
|
| Livewire is only used on the customer booking page, which loads its
| assets explicitly with @livewireStyles and @livewireScripts. Automatic
| injection is off so the React admin pages and the corporate site do not
| download Livewire (and a second copy of Alpine) for nothing.
|
*/

return [

    'inject_assets' => false,

];
