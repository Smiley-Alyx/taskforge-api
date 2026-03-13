<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: '/api/v1/auth/login'
)]
#[OA\Post(
    path: '/api/v1/auth/login',
    operationId: 'authLogin',
    tags: ['Auth'],
    summary: 'Login and get Sanctum token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK'
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]
class AuthPaths
{
}
