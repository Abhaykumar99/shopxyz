<?php

/*
|--------------------------------------------------------------------------
| Project validation wording
|--------------------------------------------------------------------------
|
| Merged over Laravel's built-in messages; only the keys below change.
| `files.*` is the field Livewire validates when an image is first uploaded
| (config/livewire.php temporary_file_upload rules).
|
| Keep `max` first: Laravel looks up size messages as "files.0.max.file",
| which the wildcard key "files.*.file" would otherwise match first.
|
*/

return [

    'custom' => [
        'files.*' => [
            'max' => ['file' => 'The image must be 4 MB or smaller.'],
            'mimes' => 'Upload a JPG, PNG or WebP image.',
            'required' => 'Choose an image to upload.',
            'file' => 'Choose an image to upload.',
        ],
    ],

];
