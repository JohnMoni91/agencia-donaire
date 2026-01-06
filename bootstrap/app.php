<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // AQUI definimos todos os atalhos para os middlewares.
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            // Se você precisar criar outros middlewares no futuro,
            // você pode adicionar os atalhos deles aqui.
        ]);

        // Você também pode registrar middlewares globais aqui se precisar no futuro.
        // ex: $middleware->append(MeuMiddlewareGlobal::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuração de tratamento de exceções.
    })->create();