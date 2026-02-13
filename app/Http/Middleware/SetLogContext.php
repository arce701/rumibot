<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SetLogContext
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenantContext->getTenantId()) {
            Context::add('tenant_id', $this->tenantContext->getTenantId());
        }

        if ($request->user()?->id) {
            Context::add('user_id', $request->user()->id);
        }

        return $next($request);
    }
}
