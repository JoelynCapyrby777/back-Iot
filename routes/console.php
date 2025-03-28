<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\ConsumeApiData;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('api:consume-data')
    ->everyTwoMinutes()
    ->onFailure(function () {
        $this->error('Error al consumir los datos de la API.');
    })
    ->onSuccess(function () {
        $this->info('Datos consumidos correctamente.');
    });