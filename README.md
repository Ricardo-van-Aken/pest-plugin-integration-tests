# Laravel End-To-End Testing

A Laravel package that allows for the easy creation of true end-to-end tests, which test the laravel application while using the full desired tech stack. Tests are created with a request builder, allowing for actual HTTP(S) requests to be sent to the application. The HTTP requests contain a unique header which the application uses to differentiate between normal requests and 'test' requests. When this header is detected the application automatically switches from its normal storage to separate testing storage for databases, cache, queues, and sessions, ensuring complete isolation between test and production environments. 

## Features

- **Storage Switching:** Automatically switches databases, cache, queues, and sessions to testing storage when `X-TESTING` header is detected
- **HTTP Request Builder:** Provides `E2ETestCase` with fluent HTTP client and request builder
- **Test Stubs:** Includes ready-to-use test stubs for common Laravel features (authentication, registration, settings, etc.)

## Requirements

- PHP ^8.1
- Laravel ^12.0

## Table of Contents

- [Installation](#installation)
- [Setup](#setup)
  - [1. Environment Configuration](#1-environment-configuration)
    - [Application Environment Variables (`.env`)](#application-environment-variables-env)
    - [Test Code Environment Variables (`phpunit.e2e.xml`)](#test-code-environment-variables-phpunite2exml)
  - [2. Publish Package Assets (Optional)](#2-publish-package-assets)
    - [Config File (`e2e-testing-config`)](#config-file-e2e-testing-config)
    - [E2E Test Stubs (`e2e-tests`)](#e2e-test-stubs-e2e-tests)
  - [3. Composer Script (Optional)](#3-composer-script-optional)
- [Storage Switching](#storage-switching)
  - [Database Switching](#database-switching)
  - [Cache Switching](#cache-switching)
  - [Queue Switching](#queue-switching)
  - [Session Switching](#session-switching)
- [Usage](#usage)
  - [Using E2ETestCase](#using-e2etestcase)
  - [HTTP Request Builder Methods](#http-request-builder-methods)
  - [Running Tests](#running-tests)

## Installation

```bash
composer require ricardo-van-aken/laravel-integration-testing
```

If the package is not published to Packagist, add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Ricardo-van-Aken/laravel-integration-testing.git"
        }
    ],
    "require-dev": {
        "ricardo-van-aken/laravel-integration-testing": "dev-main"
    }
}
```

## Setup

### 1. Environment Configuration

E2E tests work by sending HTTP requests to your application. When the application receives a request with the `X-TESTING` header, it automatically switches to testing storage (database, cache, queues, sessions) using values from your `.env` file. The `phpunit.e2e.xml` file ensures your test code uses the same testing storage configuration, keeping both sides in sync.

#### Application Environment Variables (`.env`)

Add these to your application's `.env` file. These values are used by the application when it receives test requests:

**Testing Database Configuration:**
```env
DB_DATABASE_TESTING=your_testing_database
DB_USERNAME_TESTING=your_testing_username
DB_PASSWORD_TESTING=your_testing_password
```

**Redis Queue Configuration (if using Redis for queues):**
```env
REDIS_QUEUE_DB_TESTING=13
```

**Redis Session Configuration (if using Redis for sessions):**
```env
REDIS_SESSION_DB_TESTING=14
```

**Redis Cache Configuration (if using Redis for cache):**
```env
REDIS_CACHE_DB_TESTING=15
```

Also make sure your `APP_URL` points to the correct URL.

#### Test Code Environment Variables (`phpunit.e2e.xml`)

Publish the PHPUnit configuration file:

```bash
php artisan vendor:publish --tag=e2e-testing-phpunit
```

**What it does:** Creates `phpunit.e2e.xml` in your project root.

**Purpose:** Configures the test code environment to match the application's testing storage configuration:
- Sets `APP_ENV=testing`
- Sets `DB_CONNECTION` to your testing connection. This must match the connection the package switches to (see [Database Switching](#database-switching))
- Configures Redis database numbers for cache, queue, and sessions (if using Redis) - must match `REDIS_*_DB_TESTING` values from `.env` (see [Cache Switching](#cache-switching), [Queue Switching](#queue-switching), [Session Switching](#session-switching))

**When to publish:** Always. This file ensures your test code and application code use the same storage.

**After publishing, configure `phpunit.e2e.xml` to match your `.env` values:**
- Set `DB_CONNECTION` to match your testing connection (e.g., `mysql_testing`)
- If using Redis for cache, set `REDIS_CACHE_DB` to match `REDIS_CACHE_DB_TESTING` from your `.env` (e.g., `15`)
- If using Redis for queues, set `REDIS_QUEUE_DB` to match `REDIS_QUEUE_DB_TESTING` from your `.env` (e.g., `13`)
- If using Redis for sessions, set `REDIS_SESSION_DB` to match `REDIS_SESSION_DB_TESTING` from your `.env` (e.g., `14`)


### 2. Publish Package Assets (Optional)

Publish all package assets at once:

```bash
php artisan vendor:publish --provider="RicardoVanAken\PestPluginE2ETests\TestingServiceProvider"
```

Or publish individual assets:

#### Config File (`e2e-testing-config`)

```bash
php artisan vendor:publish --tag=e2e-testing-config
```

**What it does:** Creates `config/e2e-testing.php` in your project root.

**Purpose:** Allows you to customize the package behavior:
- `header_name`: The HTTP header name that triggers storage switching (default: `X-TESTING`)
- `login_route`: The route used for user authentication in E2E tests (default: `login.store`)
- `two_factor_challenge_route`: The route for submitting 2FA challenges (default: `two-factor.login`)
- `two_factor_challenge_location_route`: The route to check if login redirected to 2FA (default: `two-factor.login`)

**When to publish:** Only if you need to customize the header name or authentication routes. The package works with defaults, so this is optional.

#### E2E Test Stubs (`e2e-tests`)

```bash
php artisan vendor:publish --tag=e2e-tests
```

**What it does:** Creates E2E test files in your `tests/E2E` directory.

**Purpose:** Provides example E2E tests based on Laravel 12 starterkit feature tests. These tests demonstrate how to use `E2ETestCase` and the `httpRequestBuilder()` method to make actual HTTP(S) requests to your application, handle authentication, and thus create true end-to-end tests using this package.

**When to publish:** When you want to see examples of how to write E2E tests using `E2ETestCase` and the request builder. Use these as a starting point for writing your own E2E tests.

**Note:** The published tests will be placed in the `tests/E2E` directory. The plugin automatically configures Pest to use `E2ETestCase` for tests in this directory.

### 3. Composer Script (Optional)

Add this to your `composer.json` scripts section:

```json
{
    "scripts": {
        "test:e2e": [
            "@php artisan config:clear --ansi",
            "@php artisan test -c phpunit.e2e.xml"
        ]
    }
}
```

**What it does:** This script provides a convenient command to run E2E tests. It clears the Laravel configuration cache and then runs tests using the `phpunit.e2e.xml` configuration file, which ensures your tests use the correct testing storage configuration.

## Storage Switching

When the application receives a request with the `X-TESTING` header, it automatically switches from default storage to separate testing storage for databases, cache, queues, and sessions. This ensures complete isolation between test and production environments.

**Note:** Some storage drivers require additional setup (such as creating an extra mysql database). See the individual sections below for details.

### Database Switching

The application automatically switches from the default database connection to a `{connection}_testing` connection (for example: `mysql` → `mysql_testing`).

**Supported and Tested:** Only MySQL is currently tested and officially supported.

**Additional Setup Required:** You must create a separate testing database in your database server. The package will use the credentials from `DB_DATABASE_TESTING`, `DB_USERNAME_TESTING`, and `DB_PASSWORD_TESTING` to connect to this database.

**Automatic Database Connection Creation:**

The package automatically creates the following connections if the corresponding base connection exists:

- `mysql_testing` (if `mysql` exists) - **✅ Tested and supported**
- `mariadb_testing` (if `mariadb` exists)
- `pgsql_testing` (if `pgsql` exists)
- `sqlite_testing` (if `sqlite` exists)
- `sqlsrv_testing` (if `sqlsrv` exists)


### Cache Switching

**Supported and Tested:** Only Redis is currently tested and officially supported.

**Redis Cache:**
- Switches to a separate Redis database number specified by `REDIS_CACHE_DB_TESTING` (default: 15)

**Additional Setup Required:** Ensure the Redis database number specified in `REDIS_CACHE_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.

**Other Cache Drivers (not tested):**
- Database cache: Uses the testing database connection (see [Database Switching](#database-switching))
- File cache: Uses `storage/framework/cache/testing` directory (automatically created, no additional setup needed)
- Array cache: Already isolated per process (no additional setup needed)
- Memcached: Uses a testing prefix (no additional setup needed, though a separate Memcached server is recommended)
- DynamoDB: Uses a testing table (requires a separate DynamoDB table to be created with `_testing` suffix)

### Queue Switching

**Supported and Tested:** Only Redis is currently tested and officially supported.

**Redis Queue:**
- Switches to a separate Redis database number specified by `REDIS_QUEUE_DB_TESTING` (default: 13)

**Additional Setup Required:** Ensure the Redis database number specified in `REDIS_QUEUE_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.

**Other Queue Drivers (not tested):**
- Database queue: Uses the testing database connection (see [Database Switching](#database-switching))
- Sync queue: Already isolated per process (no additional setup needed)
- SQS: Requires a separate queue to be created. Set `SQS_QUEUE_TESTING` environment variable with the testing queue name
- Beanstalkd: Requires a separate queue name. Set `BEANSTALKD_QUEUE_TESTING` environment variable with the testing queue name

### Session Switching

**Supported and Tested:** Only Redis is currently tested and officially supported.

**Redis Session:**
- Switches to a separate Redis database number specified by `REDIS_SESSION_DB_TESTING` (default: 14)

**Additional Setup Required:** Ensure the Redis database number specified in `REDIS_SESSION_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.

**Other Session Drivers (not tested):**
- Database sessions: Uses the testing database connection (requires testing database setup, see [Database Switching](#database-switching))
- File sessions: Uses `storage/framework/sessions/testing` directory (automatically created, no additional setup needed)
- Array sessions: Already isolated per process (no additional setup needed)
- Cookie sessions: Already isolated per request (no additional setup needed)
- Memcached: Uses a testing prefix (no additional setup needed, though a separate Memcached server is recommended)
- DynamoDB: Uses a testing table (requires a separate DynamoDB table to be created with `_testing` suffix)

## Usage

### Using E2ETestCase

The package provides an `E2ETestCase` base class that includes an HTTP request builder that automatically adds the `X-TESTING` header to all requests. This ensures that the application switches to testing storage (database, cache, queues, sessions) when processing test requests.


### HTTP Request Builder Methods

The `httpRequestBuilder()` provides the following methods for sending requests to your application:

**HTTP Methods:**
- `get($uri, $params = [])` - GET request
- `post($uri, $params = [])` - POST request
- `patch($uri, $params = [])` - PATCH request
- `put($uri, $params = [])` - PUT request
- `delete($uri, $params = [])` - DELETE request

**Request Configuration:**
- `withHeaders(array $headers)` - Add custom headers to the request
- `withRequestLogging()` - Enable request logging (logs method, URI, headers, body, and options)
- `refreshXsrf()` - Refresh the CSRF token

**Authentication:**
- `actingAs($user, $password = 'password', $recoveryCode = null)` - Authenticates a user. Automatically handles login and 2FA if enabled(assuming laravel's fortify is used). The `$recoveryCode` parameter is used for 2FA authentication.

**Execution:**
- `send()` - Sends the request and returns the response. You can also invoke the builder directly (`$this->httpRequestBuilder()->get('/')->send()` can be written as `$this->httpRequestBuilder()->get('/')()`)

### Running Tests

```bash
# Using the composer script
composer test:e2e

# Or directly
php artisan test -c phpunit.e2e.xml
```
