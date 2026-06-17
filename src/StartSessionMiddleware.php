<?php

declare(strict_types=1);

namespace Hydra\Session;

use Hydra\Session\Contracts\SessionLifecycleInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Brackets the request with the session lifecycle: open it on the way in, save
 * it on the way out.
 *
 * This is the sole caller of {@see SessionLifecycleInterface}, which is why the
 * lifecycle is kept off the controller-facing {@see SessionInterface} — a
 * controller works with the started session and never has to (or gets to) open
 * or close it.
 *
 * save() runs in a finally so the session is always persisted and closed, even
 * when an inner handler throws; the exception then propagates to the outer error
 * handler unchanged.
 */
final class StartSessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SessionLifecycleInterface $session) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->session->start();

        try {
            return $handler->handle($request);
        } finally {
            $this->session->save();
        }
    }
}
