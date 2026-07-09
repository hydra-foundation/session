<?php

declare(strict_types=1);

namespace Hydra\Session;

use Hydra\Core\Contracts\ContainerInterface;
use Hydra\Core\Environment;
use Hydra\Core\Providers\ServiceProvider;
use Hydra\Session\Contracts\SessionInterface;
use Hydra\Session\Contracts\SessionLifecycleInterface;
use Hydra\Session\Stores\NativeSessionStore;

/**
 * Wires the session package into an application.
 *
 * Unlike a pure capability package (e.g. hydrakit/validation, whose stateless
 * service just autowires), session binds an interface to a concrete backend and
 * supplies config — so it earns a provider, and ships its own rather than
 * leaving the app to hand-wire it.
 *
 * The crux is that {@see NativeSessionStore} is bound once and exposed behind
 * BOTH contracts: the controller-facing {@see SessionInterface} and the
 * middleware-facing {@see SessionLifecycleInterface} resolve to the very same
 * instance. The middleware's start()/save() and a controller's get()/set() must
 * act on one store (and one $_SESSION) within a request — two instances would
 * silently diverge.
 *
 * An app that wants a different backend (e.g. a Redis store) can register this
 * provider and then rebind SessionInterface/SessionLifecycleInterface, or simply
 * not use this provider at all.
 *
 * These singletons are request-scoped by construction: Hydra builds one
 * container per request (classic SAPI), so "singleton" means one-per-request,
 * never cross-request. The store holds per-request state and the native
 * backend additionally writes through PHP's SAPI ($_SESSION, Set-Cookie), so
 * reusing a container across requests is unsupported — see this package's
 * docs/one-container-per-request.md.
 */
final class SessionServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Typed, immutable view of the SESSION_* settings, built once.
        $container->singleton(SessionConfig::class, function () use ($container) {
            return SessionConfig::fromEnvironment($container->get(Environment::class));
        });

        // The single store instance. Bound under its own class so the two
        // interface bindings below can share it.
        $container->singleton(NativeSessionStore::class, function () use ($container) {
            return new NativeSessionStore($container->get(SessionConfig::class));
        });

        // Both contracts resolve to that one instance — see the class docblock.
        $container->singleton(SessionInterface::class, fn () => $container->get(NativeSessionStore::class));
        $container->singleton(SessionLifecycleInterface::class, fn () => $container->get(NativeSessionStore::class));

        // StartSessionMiddleware is intentionally not bound here: its only
        // dependency is SessionLifecycleInterface (bound above), so the container
        // autowires it. This provider declares only the wiring that can't be
        // inferred — the shared-instance-behind-two-interfaces trick above.
    }
}
