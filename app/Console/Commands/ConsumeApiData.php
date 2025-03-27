<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ConsumeApiData extends Command
{
    protected $signature = 'api:consume-data';
    protected $description = 'Consume datos de la API cada 2 minutos';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Hacer la solicitud HTTP a la API
        $response = Http::get('http://127.0.0.1:8000/api/consumir-datos');

        if ($response->successful()) {
            $this->info('Datos consumidos correctamente.');
        } else {
            $this->error('Error al consumir los datos de la API.');
        }
    }
}
