<?php
namespace App\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle middleware verification
     * @param string|null $param Parameter passed to middleware (e.g. role name)
     */
    public function handle(?string $param = null): void;
}
