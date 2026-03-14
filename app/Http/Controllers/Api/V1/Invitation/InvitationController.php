<?php

namespace App\Http\Controllers\Api\V1\Invitation;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invitation\AcceptInvitationRequest;
use App\Http\Requests\Api\V1\Invitation\StoreInvitationRequest;
use App\Http\Resources\Api\V1\InvitationResource;
use App\Models\Invitation;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $invitations = Invitation::query()
            ->where('workspace_id', $workspace->getKey())
            ->orderByDesc('id')
            ->paginate(50);

        return InvitationResource::collection($invitations);
    }

    public function store(StoreInvitationRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $invitation = Invitation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'email' => $request->string('email')->lower()->toString(),
            'role' => $request->string('role')->toString(),
            'token' => Str::random(48),
            'invited_by' => $request->user()->getKey(),
            'expires_at' => now()->addDays(7),
        ]);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'invitation.created',
            Invitation::class,
            (int) $invitation->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return (new InvitationResource($invitation))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Workspace $workspace, Invitation $invitation)
    {
        if ((int) $invitation->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $invitation);

        $invitation->delete();

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'invitation.deleted',
            Invitation::class,
            (int) $invitation->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }

    public function accept(AcceptInvitationRequest $request)
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($request, $user) {
            /** @var Invitation $invitation */
            $invitation = Invitation::query()
                ->where('token', $request->string('token')->toString())
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->accepted_at !== null || $invitation->expires_at->isPast()) {
                abort(422);
            }

            WorkspaceMember::query()->firstOrCreate([
                'workspace_id' => $invitation->workspace_id,
                'user_id' => $user->getKey(),
            ], [
                'role' => $invitation->role,
                'joined_at' => now(),
            ]);

            $invitation->update([
                'accepted_at' => now(),
            ]);

            return $invitation;
        });

        ActivityOccurred::dispatch(
            (int) $result->workspace_id,
            (int) $user->getKey(),
            'invitation.accepted',
            Invitation::class,
            (int) $result->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new InvitationResource($result);
    }
}
