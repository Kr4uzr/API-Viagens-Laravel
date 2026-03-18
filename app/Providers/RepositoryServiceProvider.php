<?php

namespace App\Providers;

use App\Repositories\Contracts\TravelOrderRepositoryInterface;
use App\Repositories\Eloquent\TravelOrderRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings de repositórios da aplicação.
     * Faz a injeção de dependência apontar a interface para a implementação Eloquent.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            TravelOrderRepositoryInterface::class,
            TravelOrderRepository::class,
        );
    }
}
