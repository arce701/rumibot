<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromToken
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->currentTenant) {
            abort(403, __('No active tenant context.'));
        }

        $this->tenantContext->set($user->currentTenant);

        return $next($request);
    }
}
