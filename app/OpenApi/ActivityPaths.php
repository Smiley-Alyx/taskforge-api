<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/activity',
    operationId: 'activityIndex',
    tags: ['Activity'],
    summary: 'List activity logs (workspace_id is required)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'actor_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Get(
    path: '/api/v1/workspaces/{workspace}/activity',
    operationId: 'workspaceActivityIndex',
    tags: ['Activity'],
    summary: 'List workspace activity log',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ],
)]
class ActivityPaths
{
}
