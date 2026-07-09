<?php

declare(strict_types=1);

namespace Hydra\Session\Stores;

/**
 * A pure, in-memory session store.
 *
 * The reference backend: state lives in the {@see AbstractSession} arrays and
 * never leaves the object, so the contract can be exercised with no global
 * state and no I/O. It backs tests (and any context wanting an ephemeral
 * session). {@see NativeSessionStore} is the production backend.
 *
 * Because the data is already in memory, start() only ages flash and save() has
 * nothing to persist. The id is a random token rotated on regenerate().
 *
 * The parent's lifecycle guard applies here exactly as in production: data
 * access outside start()→save() throws. That is deliberate — a reference
 * store that worked without start() could never catch lifecycle bugs in the
 * code it exists to test.
 */
final class ArraySessionStore extends AbstractSession
{
    private string $id;

    public function __construct()
    {
        $this->id = $this->newId();
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->ageFlash();
        $this->started = true;
    }

    public function save(): void
    {
        // Nothing to persist for an in-memory store — but the lifecycle still
        // closes, so post-save access fails loud like production.
        $this->started = false;
    }

    public function id(): string
    {
        $this->guardStarted();

        return $this->id;
    }

    public function regenerate(bool $deleteOld = true): void
    {
        $this->guardStarted();

        // No backing storage to drop, so $deleteOld has no effect here; the data
        // and flash carry over to the new id.
        $this->id = $this->newId();
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
