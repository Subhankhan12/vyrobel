<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Demo seed reference date
    |--------------------------------------------------------------------------
    |
    | ⚠️ OPTIONAL, AND UNSET IN NORMAL USE. The demo seeder is deliberately date-relative so the demo's
    | stays are always genuinely upcoming; that makes two of the six figures `ops/money-as-seeded.sh`
    | pins move with the calendar. Setting VY_SEED_DATE freezes "today" for a seeding run so the money
    | gate can compare against hand-written constants. Unset ⇒ the seeder uses the real today and the
    | demo is unchanged.
    |
    | ⚠️ It lives in config rather than being read with env() at the call site: env() returns null once
    | the config is cached, which would have made the money gate silently fall back to its weaker mode
    | in exactly the environment (CI) where the full check matters most.
    |
    */

    'seed_date' => env('VY_SEED_DATE'),

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),

                // ⚠️⚠️ THE ISOLATION LEVEL IS THE APPLICATION'S CHOICE, NOT THE HOST'S. READ THIS BEFORE CHANGING IT.
                //
                // ⚠️⚠️ THE NO-DOUBLE-BOOKING GUARANTEE DEPENDS ON THIS VALUE. `AssignmentService` holds the line
                // with `SELECT … FOR UPDATE` on `availability`, and what that statement DOES differs by engine
                // and by isolation level:
                //   • 🔬 MariaDB 11.6+ ships `innodb_snapshot_isolation=ON`. At REPEATABLE READ a competing
                //     `FOR UPDATE` does NOT block-then-reject — it aborts with **error 1020, "Record has changed
                //     since last read"**. The booking path then throws a raw database error instead of refusing
                //     gracefully, and 🔬 `DeadlockAvoidanceTest` shows a LEGITIMATE booking being lost that way.
                //   • 🔬 At READ COMMITTED both engines block, re-read, and reject cleanly — which is the
                //     behaviour every one of this project's concurrency tests was written against.
                //
                // 🔬 MEASURED 2026-09-20, same commit and seed, full suite each time:
                //     MySQL 8.4.10    @ REPEATABLE READ → 3,651 pass      (the historical control)
                //     MySQL 8.4.10    @ READ COMMITTED  → 3,651 pass      ⚠️ the cell nobody had run
                //     MariaDB 11.8.9  @ REPEATABLE READ → concurrency FAILS, error 1020
                //     MariaDB 11.8.9  @ READ COMMITTED  → 3,648 pass      (3 failures, separately explained)
                // ⚠️ READ COMMITTED is the ONLY level correct on BOTH engines. It is not chosen because one
                //    engine likes it; it is chosen because it is the only cell where both behave as required.
                //
                // ⚠️⚠️ AND IT IS SET HERE, IN `options`, ON PURPOSE. PDO applies this at CONNECT time, so it
                // reaches a web request, an `artisan` command, a QUEUED JOB and a SCHEDULED TASK alike. A
                // middleware or a request-scoped hook would leave the queue worker — where payment webhooks
                // fulfil and payouts are written — running on whatever the host happens to default to, which is
                // precisely the dependency this removes. ⚠️ It also re-applies on reconnect, which matters here:
                // 🔬 the production host drops idle connections after 20 seconds.
                //
                // ⚠️ Until 2026-09-20 this was unset, and production was READ COMMITTED only because Hostinger's
                // server default happened to be. Nothing in the repository chose or asserted it.
                // Pinned by `DatabaseIsolationLevelTest`. See `docs/KNOWN-LIMITS.md` L12b.
                Mysql::ATTR_INIT_COMMAND => 'SET SESSION TRANSACTION ISOLATION LEVEL '
                    .str_replace('-', ' ', strtoupper((string) env('DB_TRANSACTION_ISOLATION', 'READ COMMITTED'))),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),

                // ⚠️⚠️ THE ISOLATION LEVEL IS THE APPLICATION'S CHOICE, NOT THE HOST'S. READ THIS BEFORE CHANGING IT.
                //
                // ⚠️⚠️ THE NO-DOUBLE-BOOKING GUARANTEE DEPENDS ON THIS VALUE. `AssignmentService` holds the line
                // with `SELECT … FOR UPDATE` on `availability`, and what that statement DOES differs by engine
                // and by isolation level:
                //   • 🔬 MariaDB 11.6+ ships `innodb_snapshot_isolation=ON`. At REPEATABLE READ a competing
                //     `FOR UPDATE` does NOT block-then-reject — it aborts with **error 1020, "Record has changed
                //     since last read"**. The booking path then throws a raw database error instead of refusing
                //     gracefully, and 🔬 `DeadlockAvoidanceTest` shows a LEGITIMATE booking being lost that way.
                //   • 🔬 At READ COMMITTED both engines block, re-read, and reject cleanly — which is the
                //     behaviour every one of this project's concurrency tests was written against.
                //
                // 🔬 MEASURED 2026-09-20, same commit and seed, full suite each time:
                //     MySQL 8.4.10    @ REPEATABLE READ → 3,651 pass      (the historical control)
                //     MySQL 8.4.10    @ READ COMMITTED  → 3,651 pass      ⚠️ the cell nobody had run
                //     MariaDB 11.8.9  @ REPEATABLE READ → concurrency FAILS, error 1020
                //     MariaDB 11.8.9  @ READ COMMITTED  → 3,648 pass      (3 failures, separately explained)
                // ⚠️ READ COMMITTED is the ONLY level correct on BOTH engines. It is not chosen because one
                //    engine likes it; it is chosen because it is the only cell where both behave as required.
                //
                // ⚠️⚠️ AND IT IS SET HERE, IN `options`, ON PURPOSE. PDO applies this at CONNECT time, so it
                // reaches a web request, an `artisan` command, a QUEUED JOB and a SCHEDULED TASK alike. A
                // middleware or a request-scoped hook would leave the queue worker — where payment webhooks
                // fulfil and payouts are written — running on whatever the host happens to default to, which is
                // precisely the dependency this removes. ⚠️ It also re-applies on reconnect, which matters here:
                // 🔬 the production host drops idle connections after 20 seconds.
                //
                // ⚠️ Until 2026-09-20 this was unset, and production was READ COMMITTED only because Hostinger's
                // server default happened to be. Nothing in the repository chose or asserted it.
                // Pinned by `DatabaseIsolationLevelTest`. See `docs/KNOWN-LIMITS.md` L12b.
                Mysql::ATTR_INIT_COMMAND => 'SET SESSION TRANSACTION ISOLATION LEVEL '
                    .str_replace('-', ' ', strtoupper((string) env('DB_TRANSACTION_ISOLATION', 'READ COMMITTED'))),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
