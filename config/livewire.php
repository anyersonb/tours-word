<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | F-4 (docs/lote-3/seguridad-2026-09-14.md, Medio). Without this file,
    | Livewire falls back to its own package config
    | (vendor/livewire/livewire/config/livewire.php), which leaves
    | 'temporary_file_upload.disk' => null — Livewire then resolves it to
    | whatever config('filesystems.default') is, i.e. FILESYSTEM_DISK.
    | Today FILESYSTEM_DISK=local keeps the temporary upload endpoint
    | (livewire/upload-file, which accepts ANY file type — the image
    | whitelist in App\Filament\Support\SecureImageUpload only runs when the
    | FORM is submitted, not on the temporary upload itself) outside the
    | docroot, in storage/app/private.
    |
    | The risk the audit flagged: FILESYSTEM_DISK=public is exactly the kind
    | of change someone makes in good faith the day "images don't show up"
    | in production — and 'public' IS inside the docroot via the
    | public/storage symlink, which today has no PHP-execution guard other
    | than this very fact (see F-2, storage/app/public/.htaccess). That
    | would turn the unrestricted temporary-upload endpoint into remote
    | code execution with zero further steps, without even needing to save
    | a form.
    |
    | Pinning 'disk' explicitly to 'local' here means the temporary upload
    | destination no longer depends on FILESYSTEM_DISK at all — the two
    | concerns (where PERMANENT files end up vs. where Livewire drops
    | in-flight uploads before validation) are decoupled by design, not by
    | remembering to keep an unrelated .env value correct forever.
    |
    | NOTE: LivewireServiceProvider::mergeConfigFrom() only merges MISSING
    | top-level keys from the package default into this file — any key
    | present here (like "temporary_file_upload") REPLACES the package's
    | sub-array wholesale, it does not deep-merge. Every other default from
    | vendor/livewire/livewire/config/livewire.php's 'temporary_file_upload'
    | (directory, middleware throttle, preview_mimes, max_upload_time,
    | cleanup) is repeated below on purpose so overriding "disk" doesn't
    | silently drop the upload rate limit or the 24h cleanup.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['required', 'file', 'max:12288'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
