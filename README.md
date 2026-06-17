# Hydra Session

A request-scoped key/value store that persists across requests for one client,
backed by native `$_SESSION` behind two deliberately split interfaces. The data
surface goes to controllers; the lifecycle surface is held only by the
framework's session middleware.

## The two surfaces

`SessionInterface` is everyday data — `get`/`set`/`has`/`remove`/`all`/`clear`,
the `id`/`regenerate` controls for a privilege change, and `flash`/`getFlash`
for values that live exactly one request. Falsy values (`'0'`, `0`, `false`,
`''`) are real stored values; only an absent key is "missing".

`SessionLifecycleInterface` (`start`/`save`) is held only by
`StartSessionMiddleware`, which opens the session on the way in and persists it
on the way out. Keeping these methods off the data surface is the boundary: a
controller literally cannot close the session early and lose later writes.

```php
$session->set('cart', $items);
$session->flash('status', 'Saved.');     // readable only on the next request
$session->regenerate();                  // on a privilege change
```

## Config

`SessionConfig` maps directly onto PHP's session cookie params and is built once
from the environment. Defaults are safe-by-default for a first-party app:
http-only, `Lax` same-site, session-length cookie. `secure` defaults to false so
local http works out of the box — set `SESSION_SECURE=true` over https. Invalid
combinations (a bad `SameSite`, or `None` without `secure`) fail loudly at
construction rather than flowing silently into PHP.
