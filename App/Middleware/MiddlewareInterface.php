<?php
/**
 * Middleware interface that all middleware must implement.
 */

namespace App\Middleware;

/**
 * Interface MiddlewareInterface
 */
interface MiddlewareInterface
{
    /**
     * Process the middleware logic.
     *
     * This method should contain the middleware logic.
     * It can modify the request, response, or terminate the request early.
     */
    public function process(): void;
}