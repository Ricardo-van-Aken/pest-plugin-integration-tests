<?php

namespace RicardoVanAken\PestPluginIntegrationTests;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class IntegrationTestCase extends BaseTestCase
{
    protected $client;
    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // For each test, create a new client with refreshed cookiejar
        $this->client = new Client([
            'base_uri' => env('APP_URL', 'https://localhost'),
            'verify' => false,
            'cookies' => new CookieJar(),
        ]);

        
        // Get the current runtime connection
        $currentConnection = DB::getDefaultConnection();
        
        // Check if the current connection ends with _testing
        if (!str_ends_with($currentConnection, '_testing')) {
            $this->fail(
                "Integration Tests must use a database connection ending with '_testing', to make sure they are not ran on your default database." .
                "The current database connection is: {$currentConnection}. " .
                "Make sure phpunit.integration.xml sets DB_CONNECTION to a connection ending with '_testing' (e.g., mysql_testing, pgsql_testing)."
            );
        }

        // Check if the testing connection exists
        if (!config("database.connections.{$currentConnection}")) {
            $this->fail(
                "The testing database connection '{$currentConnection}' is not configured. " .
                "If you are using a different database than the default ones used by the LaravelIntegrationTesting package, " .
                "you need to configure the testing connection in config/database.php."
            );
        }


        // Set up the database by migrating it once for all tests
        if (!static::$migrated) {
            // Migrate using the testing connection
            Artisan::call('migrate');
            static::$migrated = true;
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * HTTP request builder for integration tests.
     */
    protected function httpRequestBuilder()
    {
        return new class($this->client) {
            private $client;
            private $pendingRequest = [];
            private $xsrfToken = null;

            public function __construct($client)
            {
                $this->client = $client;
            }

            public function get($uri, $params = [])
            {
                $this->pendingRequest = [
                    'method' => 'GET',
                    'uri' => $uri,
                    'params' => $params,
                ];
                return $this;
            }

            public function post($uri, $params = [])
            {
                $this->pendingRequest = [
                    'method' => 'POST',
                    'uri' => $uri,
                    'params' => $params,
                ];
                return $this;
            }

            public function patch($uri, $params = [])
            {
                $this->pendingRequest = [
                    'method' => 'PATCH',
                    'uri' => $uri,
                    'params' => $params,
                ];
                return $this;
            }

            public function put($uri, $params = [])
            {
                $this->pendingRequest = [
                    'method' => 'PUT',
                    'uri' => $uri,
                    'params' => $params,
                ];
                return $this;
            }

            public function delete($uri, $params = [])
            {
                $this->pendingRequest = [
                    'method' => 'DELETE',
                    'uri' => $uri,
                    'params' => $params,
                ];
                return $this;
            }

            private function getXsrfToken()
            {
                // Use lightweight route to set CSRF token cookie
                $response = $this->get('/test/csrf-token')->send();
                
                $data = json_decode($response->getBody(), true);

                return $data['csrf_token'] ?? null;
            }

            public function withXsrf()
            {
                // Get xsrf token only when it doesn't exist yet to avoid making unnecessary HTTP requests.
                dump('Getting xsrf token');
                dump('old Xsrf token: ' . $this->xsrfToken);
                $this->xsrfToken ??= $this->getXsrfToken();
                dump('new Xsrf token: ' . $this->xsrfToken);
                return $this;
            }

            public function actingAs($user, $password = 'password')
            {
                // Get the login url
                $loginConfig = config('integration-testing.login_route', '/login');
                $loginRoute = $loginConfig;

                // If the route doesn't start with '/', try to resolve it as a route name
                if (!str_starts_with($loginConfig, '/')) {
                    try {
                        $loginRoute = route($loginRoute);
                    } catch (\Exception $e) {
                        throw new \Exception('Login route not found: ' . $loginRoute);
                    }
                }
                
                // Log in the user
                $response = $this->withXsrf()->post($loginRoute, [
                    'email' => $user->email,
                    'password' => $password,
                ])->send();

                // Refresh xsrf token after authentication
                dump('Refreshing xsrf token');
                $this->xsrfToken = null;
                $this->withXsrf();

                return $this;
            }

            public function send()
            {
                if (!isset($this->pendingRequest['method']) || !isset($this->pendingRequest['uri'])) {
                    throw new \Exception('HTTP method and URI must be set before calling send().');
                }

                $method = $this->pendingRequest['method'];
                $uri = $this->pendingRequest['uri'];
                $params = $this->pendingRequest['params'] ?? [];

                $options = [
                    'allow_redirects' => false,
                ];

                if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'])) {
                    $options['form_params'] = $params;
                } else {
                    $options['query'] = $params;
                }

                // Always add the header to make sure the application receiving the request knows it's a testing request.
                $headerName = config('integration-testing.header_name', 'X-TESTING');
                $options['headers'][$headerName] = 1;

                if ($this->xsrfToken) {
                    $options['headers']['X-CSRF-TOKEN'] = $this->xsrfToken;
                }

                $response = $this->client->request($method, $uri, $options);

                // Reset only the builder's request
                $this->pendingRequest = [];

                return $response;
            }

            public function __invoke()
            {
                return $this->send();
            }
        };
    }
}

