<?php

namespace RicardoVanAken\PestPluginE2ETests;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class E2ETestCase extends BaseTestCase
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
            'handler' => HandlerStack::create(),
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
        // Clear cache to ensure test isolation (resets rate limiters, session data, etc.). Make sure
        // tests dont use the same cache as the application.
        Cache::flush();
        
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
            private $headers = [];
            private $xsrfToken = null;

            public function __construct($client)
            {
                $this->client = $client;
                $this->xsrfToken = $this->getXsrfToken();
            }

            public function withRequestLogging()
            {
                $handlerStack = $this->client->getConfig('handler');

                $handlerStack->push(Middleware::tap(function ($request, $options) {
                    Log::debug('E2E request', [
                        'method' => $request->getMethod(),
                        'uri' => (string) $request->getUri(),
                        'headers' => $request->getHeaders(),
                        'body' => (string) $request->getBody(),
                        'options' => $options,
                    ]);
                }));

                return $this;
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

            public function refreshXsrf()
            {
                $this->xsrfToken = $this->getXsrfToken();
                return $this;
            }

            public function withHeaders(array $headers)
            {
                $this->headers = array_merge($this->headers, $headers);
                return $this;
            }

            public function actingAs($user, $password = 'password')
            {
                // Get the login url
                $loginConfig = config('e2e-testing.login_route', '/login');
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
                $this->post($loginRoute, [
                    'email' => $user->email,
                    'password' => $password,
                ])->send();

                // Refresh xsrf token after authentication
                $this->xsrfToken = $this->getXsrfToken();

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
                } elseif (in_array($method, ['GET'])) {
                    // Add params to the query string
                    $options['query'] = $params;

                    // Merge the existing query params from the url with the new query params
                    $existing_query_params = [];
                    $url_query = parse_url($uri, PHP_URL_QUERY) ?? '';
                    parse_str($url_query, $existing_query_params);
                    $options['query'] = array_merge($existing_query_params, $params);
                }

                // Initialize headers array
                $options['headers'] = [];
                // Add the csrf token if available
                if ($this->xsrfToken) {
                    $options['headers']['X-CSRF-TOKEN'] = $this->xsrfToken;
                }
                // Merge any custom headers that were set via withHeaders()
                if (!empty($this->headers)) {
                    $options['headers'] = array_merge($options['headers'], $this->headers);
                }
                // Always add the X-TESTING header to make sure the application receiving the request knows it's a testing request.
                $options['headers'][config('e2e-testing.header_name', 'X-TESTING')] = 1;

                $response = $this->client->request($method, $uri, $options);

                // Reset the builder's request and headers
                $this->pendingRequest = [];
                $this->headers = [];

                return $response;
            }

            public function __invoke()
            {
                return $this->send();
            }
        };
    }

}

