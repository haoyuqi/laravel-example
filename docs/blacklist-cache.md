# Blacklist cache consistency

## 1. Overview

The blacklist cache is a Redis hash used by `BlackListService::checkIp` to remember whether an IP address is currently blacklisted. The request path can check Redis before asking the database, avoiding a database query on every request for an address whose decision is already cached.

Both outcomes are useful cache entries. A cached positive (`'1'`) keeps repeated blocked requests off the database. A cached negative (`'0'`) avoids repeated database lookups for ordinary visitor traffic.

Before the consistency changes for issue #140, database writes from the admin interface did not remove the corresponding cached field. A cached negative could therefore keep allowing a newly blacklisted address. A cached positive could keep blocking an address after its record was deleted or restored. Those decisions remained stale until the scheduled key deletion removed the daily hash.

The consistency strategy invalidates only affected IP fields when a blacklist model changes. The service also tolerates Redis failures on reads and cache writes by falling back to the database decision.

## 2. Cache storage format

Each calendar day has its own Redis hash. Its application key follows this pattern:

```text
black_list_<YYYY-MM-DD>
```

For example, the application key for October 1, 2026 is `black_list_2026-10-01`. The hash field is the IP address as a string. Its value is `'1'` when the address is blacklisted, or `'0'` when it is not.

Both positive and negative decisions are stored. A missing field has a different meaning from a cached negative: missing means the service has no cached decision and should query the database.

A cached decision applies to the daily hash selected by the current date. When the date changes, a request uses that day's hash, even if yesterday's key still exists. The scheduled deletion removes previous hashes so they do not accumulate indefinitely.

The stored values are strings returned through the Redis hash interface. In particular, `'0'` is a present field, not an empty or missing result. The read path must preserve that distinction when converting the value into a boolean.

The hash is stored on the Redis `default` connection, which uses Redis database 0. That connection applies the configured connection-level `REDIS_PREFIX` automatically through PhpRedis. Application code passes the unprefixed key to Laravel Redis commands. Do not prepend `REDIS_PREFIX` in application code or when following the logical key examples in this document.

You can inspect the hash using Redis CLI:

```text
HGETALL black_list_2026-10-01
```

The client connection's configured prefix may affect the physical key visible to tools that connect without the application's Redis configuration. The command above shows the logical application key.

A hash keeps the day's IP decisions grouped under one date key. Per-IP invalidation can remove one field without deleting other decisions in the same hash.

## 3. Read path: `BlackListService::checkIp`

For the current date, `checkIp` builds the key through `BlackListService::cacheKey()` and performs one `HGET` for the requested IP. A single lookup avoids a gap between a separate existence check and value read.

The return value distinguishes the two cache states:

| `HGET` result | Meaning | Action |
| --- | --- | --- |
| `null` or PhpRedis `false` | No field exists for the IP | Query the database |
| `'0'` | Cached as not blacklisted | Return `false` |
| `'1'` | Cached as blacklisted | Return `true` and dispatch `BlackListLog` |

On a cache hit, the service returns the cached decision. When that decision is blocked, it dispatches a `BlackListLog` job as it does for a database-confirmed block.

A cache hit does not need a database lookup to decide whether the address is blocked. The logging job is still dispatched on positive hits so blocked requests continue to be recorded.

On a cache miss, the service queries the blacklist table for the IP. It writes `'1'` or `'0'` to the day's hash based on the database result, then applies the 48-hour TTL described below. It returns the database-derived decision and dispatches the logging job if the IP is blacklisted.

The cache stores the result after the database lookup, so the value reflects the database state observed by that lookup. An admin change clears that IP's field so the next ordinary lookup can obtain a new result.

If the Redis read fails, the service logs a warning and continues as a cache miss, so it can obtain the decision from the database. If writing the result or its TTL fails, the service logs a warning and still returns the database-derived decision. Redis failure must not turn a database result into an exception.

Database queries and the `BlackListLog` dispatch are outside the Redis failure handling. Errors from those operations are not treated as cache failures and are allowed to propagate normally.

## 4. TTL policy

After every successful `HSET`, the service issues `EXPIRE key 172800`. The expiration is 172,800 seconds, or 48 hours.

The policy means that a key self-destructs at most 48 hours after its last successful write. It is not a fixed expiration time at the end of a calendar day. Each later successful write refreshes the expiration window for the whole hash key.

Because expiration is set on the hash key, a write for any IP refreshes the lifetime of the entire day's hash. The TTL does not independently expire individual fields.

TTL is a secondary safeguard. The scheduler is responsible for the primary daily cleanup, described in section 7.

There is a narrow failure window between `HSET` and `EXPIRE`. If Redis accepts the hash write but the subsequent expiration command fails, the key can remain without a TTL. It stays until a later successful write applies an expiration or the scheduler deletes it.

## 5. Invalidation triggers

`App\Observers\BlackListObserver` is registered with the `BlackList` model in `AppServiceProvider`. It responds to model lifecycle events and asks `BlackListService::forgetIp($ip)` to remove affected fields from the current day's hash.

The `saved` handler applies these rules:

The observer uses the model's change information from the save to decide whether a cached decision could have changed. A timestamp-only touch is not a blacklist decision change and therefore does not need an `HDEL`.

| Save change | Fields invalidated |
| --- | --- |
| A newly created blacklist record | The current IP |
| The `ip` column changed | Both the old IP and the new IP |
| Only `deleted_at` changed | The current IP |
| No relevant column changed, such as a timestamp-only touch from `BlackListLog` | None |

Creation has no old IP value to invalidate, so the observer invalidates only the new record's IP. When the IP changes, both addresses must be cleared: the old address may now be allowed, while the new address may now be blocked.

The `deleted` handler invalidates the record's current IP. The `restored` handler also invalidates the current IP. Restoring a soft-deleted row may produce both a `saved` event for `deleted_at` and a `restored` event. Repeating the same `HDEL` is harmless.

The observer's `saved` guard matters because saving a `BlackListLog` touches its related blacklist record. That timestamp-only touch fires `saved`; it must not evict the positive cache entry for every blocked request.

Invalidation is always per field with `HDEL`. It does not flush a Redis database or remove unrelated fields. The observer catches Redis errors and does not throw them back through model persistence. If Redis is unavailable, the model save still succeeds, the failure is logged, and the stale field can remain until TTL expiry or scheduler cleanup.

## 6. Covered write paths

These Filament actions persist or restore `BlackList` models. Eloquent events route them through the observer, so they do not need separate cache invalidation hooks in each page or action.

| Admin or visitor action | Event that invalidates the cache |
| --- | --- |
| Create page form submit | `saved`, newly created record |
| Edit page save without an IP change | `saved`, when a relevant field changed |
| Edit page save with an IP change | `saved`, old and new IP |
| Edit page header delete action | `deleted` |
| Table row delete action | `deleted` |
| Table bulk delete action | `deleted` for each deleted record |
| Visitor resource `add_to_black_list` bulk action, first-time save | `saved`, newly created record |
| Visitor resource `add_to_black_list` bulk action, restore of a soft-deleted record | `saved` and `restored` |

The visitor action can either save a first-time record or restore an existing soft-deleted record. Both paths emit Eloquent lifecycle events and therefore invalidate the cache without relying on Filament page hooks.

Bulk delete is covered per record: each deleted model emits its own `deleted` event, and the observer removes that record's IP field. This keeps invalidation scoped to the actual records affected by the action.

## 7. Scheduler backup

The scheduled `delete:redis-cache` command removes the whole daily hash for a date. The schedule in `routes/console.php` includes:

These schedule entries operate on date-specific keys, so they can remove an entire day's hash at once. They are separate from the per-IP `HDEL` operations used immediately after admin changes.

| Time | Key date |
| --- | --- |
| 00:20 daily | Yesterday |
| 07:00 and 13:00 daily | Today |

The scheduler builds the keys using the existing `black_list_<date>` strings. Those hardcoded key strings in `routes/console.php` are intentionally preserved, and the schedule behavior is unchanged.

`app/Console/Commands/DeleteRedisCacheCommand.php` checks whether the requested key exists and deletes that key. This scheduled deletion is the primary daily cleanup. The 48-hour expiration attached to writes is the secondary safeguard if a scheduled cleanup does not run.

## 8. Consistency guarantee and known races

After an admin write commits and its Redis invalidation succeeds, the next `checkIp` call observes the fresh decision, assuming Redis remains reachable. The observer invalidates the changed IP field as part of the model lifecycle, so a subsequent lookup misses the cache and reads the committed database state.

This guarantee covers the normal sequence where the write and invalidation complete before the next check. It does not serialize already-running requests with admin changes.

A request arriving after a completed invalidation sees a missing cache field and follows the database read path. This is the behavior intended for the next request after an admin update.

One accepted race is stale refill. A request can miss the cache and read the old database state. Then an admin write can commit and invalidate the field. After that invalidation, the in-flight request can write the old result back into Redis. A later request may see that stale value until another invalidation or cleanup. Preventing this race would require request-level coordination, such as locks or versioning, and is outside the scope of issue #140.

Redis unavailability creates another limitation. Invalidation failures are logged but are not retried. Any stale entry can remain until a later successful update, its TTL expiration, or scheduler cleanup. During an outage, read failures fall back to the database, but a successful database read cannot make cache invalidation reliable while Redis is unavailable.

## 9. Failure handling rules

Redis errors are logged with context and handled according to the operation:

A read failure does not produce a fabricated cache answer. The service treats the failed read as unavailable and asks the database for the current decision.

| Operation | Warning message | Result |
| --- | --- | --- |
| Cache read in `checkIp` | `blacklist cache read failure` | Continue with the database lookup |
| Cache write or TTL update during a read miss | `blacklist cache write failure` (includes `ip` or `key` and `error`) | Return the database-derived decision |
| Observer invalidation | `blacklist cache invalidation failure` | Do not throw; let model persistence succeed |

The service catches Redis failures around Redis operations only. Database queries and the `BlackListLog` dispatch are never caught as Redis failures. This boundary keeps cache degradation separate from application failures in persistence or job dispatch.

Observer failures are logged with the affected IP and error message. The failure is visible to operators, while the admin's database change can still complete.

## 10. Operational rules

- Never use `FLUSHDB` or `FLUSHALL` to clear blacklist cache data. The Redis instance is shared by other application data, including sessions, queues, and broadcasts.
- For an individual address, use `BlackListService::forgetIp($ip)`. It removes that IP's field from the current day's hash.
- When application code needs the daily hash key, use `BlackListService::cacheKey()`. This keeps key construction in the service and avoids duplicating the date format.
- Treat the `black_list_<date>` values in `routes/console.php` as intentional scheduler key strings. They remain hardcoded so the existing scheduled cleanup behavior stays unchanged.
- Do not manually prepend the connection's `REDIS_PREFIX` when using Laravel's Redis connection. The connection applies that prefix automatically.

Per-field deletion preserves other addresses in the same hash and leaves unrelated Redis keys untouched. To inspect behavior safely, use `HGET`, `HGETALL`, and `TTL` against the logical key rather than issuing database-wide flush commands.

## 11. Testing

`tests/Feature/Service/BlackListCacheInvalidationTest.php` covers cache invalidation through the admin and visitor write paths. It checks that stale positive or negative fields are removed, that the next `checkIp` result agrees with the database, and that unrelated Redis data survives per-IP invalidation. Failure cases cover Redis read fallback and Redis invalidation failure without losing the model save.

`tests/Unit/Service/BlackListServiceKeyTest.php` covers daily key construction and the service's focused Redis key and TTL behavior, including the key format and per-IP invalidation contract.

Feature coverage also protects the lifecycle wiring that unit tests of key construction cannot establish on their own. The visitor bulk action is included because it performs model persistence outside the standard blacklist resource form pages.

The tests isolate Redis data with a unique namespace prefix so their keys do not collide with application or other test data. Setup retains the real Redis connection. Teardown restores that retained connection and uses it to clean up test keys, even if a Redis facade mock was used during a failure test. This avoids relying on a mocked facade for cleanup and prevents test-specific Redis state from leaking into later tests.
