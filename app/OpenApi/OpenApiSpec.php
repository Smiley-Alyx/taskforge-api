<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TaskForge API',
    description: 'Production-like REST API for task and project management.'
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Local Docker'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header',
    description: 'Enter token in format (Bearer <token>)'
)]
class OpenApiSpec
{
}
