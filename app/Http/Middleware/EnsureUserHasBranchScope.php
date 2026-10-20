<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards routes that require an authenticated account of any kind.
 *
 * Super Admins are allowed through with a null branch scope (meaning "all
 * branches"); branch staff must be attached to a branch.
 */
class EnsureUserHasBranchScope
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($user->isStaff() && ! $user->isSuperAdmin() && $user->branch_id === null) {
            abort(403, 'Your account is not assigned to a branch yet. Contact head office.');
        }

        return $next($request);
    }
}
