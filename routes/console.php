<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('blueprint:about', function (): void {
    $this->info('ApiBlueprint foundation is installed.');
})->purpose('Show the ApiBlueprint foundation status');
