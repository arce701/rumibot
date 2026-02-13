<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->currentTenant) {
            $this->tenantContext->set($user->currentTenant);
        }

        return $next($request);
    }
}
