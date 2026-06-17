<?php

declare(strict_types=1);

namespace Hydra\Session\Contracts;

/**
 * The everyday, controller-facing session: a request-scoped key/value store
 * that persists across requests for one client.
 *
 * Two concerns live here:
 *
 *  - Data (id/regenerate/get/set/has/remove/all/clear) — the values you stash
 *    for a client, plus the id controls used on a privilege change.
 *  - Flash (flash/getFlash) — values that live for exactly the next request.
 *
 * The lifecycle (opening, aging flash, persisting) is deliberately NOT here —
 * it belongs to {@see SessionLifecycleInterface}, which only the framework's
 * session middleware holds, so a controller can never close the session out
 * from under the rest of the request.
 *
 * Implementations must treat falsy values ('0', 0, false, '') as real, stored
 * values; only an absent key (or one explicitly set to null) is "missing".
 */
interface SessionInterface
{
    /** The current session id. */
    public function id(): string;

    /**
     * Issue a fresh session id, keeping the data. Defends against session
     * fixation — call it on any privilege change (e.g. login). $deleteOld asks
     * the backend to drop the old session's storage.
     */
    public function regenerate(bool $deleteOld = true): void;

    /** Read a stored value, or $default when the key is absent. */
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    /** Whether the key holds a non-null value. */
    public function has(string $key): bool;

    public function remove(string $key): void;

    /** @return array<string, mixed> */
    public function all(): array;

    /** Empty the stored data. Pending flash is unaffected. */
    public function clear(): void;

    /** Stash a value readable only on the next request, then gone. */
    public function flash(string $key, mixed $value): void;

    /** Read a value flashed on the previous request, or $default. */
    public function getFlash(string $key, mixed $default = null): mixed;
}
