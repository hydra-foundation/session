<?php

declare(strict_types=1);

namespace Hydra\Session\Contracts;

/**
 * The lifecycle half of a session, held only by the framework's session
 * middleware — never handed to controllers.
 *
 * Keeping these two methods off {@see SessionInterface} is the boundary: a
 * controller is given the data surface and literally cannot {@see save()} (and
 * so cannot close the session early, leaving later writes to vanish). The
 * middleware brackets the request — start() on the way in, save() on the way
 * out — and is the single caller of both.
 */
interface SessionLifecycleInterface
{
    /**
     * Open the session and age flash data (promote this-request flash to
     * readable, discard the previous request's). Idempotent within a request.
     */
    public function start(): void;

    /** Persist the session and release it for the rest of the request. */
    public function save(): void;
}
