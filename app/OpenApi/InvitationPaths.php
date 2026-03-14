<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/workspaces/{workspace}/invitations',
    operationId: 'invitationsIndex',
    tags: ['Invitations'],
    summary: 'List workspace invitations',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ],
)]
#[OA\Post(
    path: '/api/v1/workspaces/{workspace}/invitations',
    operationId: 'invitationsStore',
    tags: ['Invitations'],
    summary: 'Create invitation',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Delete(
    path: '/api/v1/workspaces/{workspace}/invitations/{invitation}',
    operationId: 'invitationsDestroy',
    tags: ['Invitations'],
    summary: 'Delete invitation',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'workspace', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'invitation', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ],
)]
#[OA\Post(
    path: '/api/v1/invitations/{token}/accept',
    operationId: 'invitationsAcceptByToken',
    tags: ['Invitations'],
    summary: 'Accept invitation by token',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Invalid/expired token'),
    ],
)]
#[OA\Post(
    path: '/api/v1/invitations/{token}/decline',
    operationId: 'invitationsDeclineByToken',
    tags: ['Invitations'],
    summary: 'Decline invitation by token',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Invalid/expired token'),
    ],
)]
class InvitationPaths
{
}
