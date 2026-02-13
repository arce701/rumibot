<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenantContext->hasTenant()) {
            abort(403, __('No active tenant context.'));
        }

        return $next($request);
    }
}
