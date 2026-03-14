<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/workspaces',
    operationId: 'workspacesIndex',
    tags: ['Workspaces'],
    summary: 'List workspaces available to current user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ],
)]
#[OA\Post(
    path: '/api/v1/workspaces',
    operationId: 'workspacesStore',
    tags: ['Workspaces'],
    summary: 'Create workspace',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'slug'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'slug', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Get(
    path: '/api/v1/workspaces/{workspace}',
    operationId: 'workspacesShow',
    tags: ['Workspaces'],
    summary: 'Get workspace',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
#[OA\Patch(
    path: '/api/v1/workspaces/{workspace}',
    operationId: 'workspacesUpdate',
    tags: ['Workspaces'],
    summary: 'Update workspace',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'slug', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Delete(
    path: '/api/v1/workspaces/{workspace}',
    operationId: 'workspacesDestroy',
    tags: ['Workspaces'],
    summary: 'Delete workspace',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ],
)]
class WorkspacePaths
{
}
