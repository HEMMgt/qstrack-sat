<?php

namespace App\View\Composers;

use App\Services\Sat\SatHealthCheck;
use Illuminate\View\View;

class SatStatusComposer
{
    public function __construct(private readonly SatHealthCheck $health) {}

    public function compose(View $view): void
    {
        $view->with([
            'satIsUp' => $this->health->isUp(),
            'satEnvironment' => config('sat.environment'),
            'satBaseUrl' => config('sat.base_url'),
        ]);
    }
}
