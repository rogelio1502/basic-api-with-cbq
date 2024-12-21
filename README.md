# Basic API with CBQ

## System Requirements

To successfully run this project, ensure you have the following dependencies installed:

* **PHP 8.3**: Install using Homebrew:
  ```
  brew install php@8.3
  ```
* **Composer**: Install Composer via Homebrew:
  ```
  brew install composer
  ```
* **librdkafka**: Required for Kafka integration:
  ```
  brew install librdkafka
  ```
* **Kafka PHP Extension**: Add the following line to your PHP configuration file to enable the Kafka extension:
  ```
  echo "extension=rdkafka.so" >> /opt/homebrew/etc/php/8.3/php.ini
  ```

## Instructions to Set Up and Start the Local Environment

1. Install the project dependencies:
   ```
   composer install
   ```
2. Run the database migrations:
   ```
   php artisan migrate
   ```
3. Start the local development server:
   ```
   php artisan serve
   ```
4. Open a separate terminal and run the Kafka daemon. Allow at least 10 seconds for the Kafka setup to initialize:
   ```
   php artisan uxmaltech:cbq-daemon --broker=default
   ```

### Important Considerations

* If you modify the application code, you **must stop** the `cbq-daemon` process and start it again with:

  ```
  php artisan uxmaltech:cbq-daemon --broker=default
  ```

  Failure to do so will result in your changes not being applied.

## Project Overview

This project incorporates concepts from **Domain-Driven Design (DDD)** and **Message-Driven Development (MDD)**. The folder structure follows a clear and modular approach, as outlined below:

* `app`
  * `Domains`
    * `Public` (Handles public-facing functionalities)
      * `Product`
        * `Commands` (Classes responsible for modifying the database)
          * `Create.php`
          * `Update.php`
          * `Delete.php`
        * `Queries` (Classes responsible for retrieving data from the database)
          * `Show.php`
          * `Index.php`

## Configuration File

To enable the correct functionality of the CBQ system, you need to include the following configuration file in your project:

```php
<?php

return [
    'name' => 'basic-api-with-cbq',
    'prefix' => 'ef-admin',
    'domain' => '',
    'subdomain' => 'mty01',
    'cbq' => [
        'controllerClass' => \Uxmal\Backend\Controllers\CBQToBrokerController::class,
        'broker' => [
            'default' => [
                'driver' => 'kafka',
                'receive_wait_timeout_ms' => env('UXMAL_BACKEND_CBQ_RECEIVE_WAIT_TIMEOUT', 5000),
                'sync_timeout_ms' => env('UXMAL_BACKEND_CBQ_SYNC_TIMEOUT', 5000),
                'kafka' => [
                    'brokers' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_BROKERS', 'localhost:9092'),
                    'group_id' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_GROUP_ID', 'uxmal-backend'),
                    'librdkafka-config' => [
                        'enable.idempotence' => 'true',
                        'socket.timeout.ms' => '50',
                    ],
                    'topics' => explode('|',env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_TOPICS', 'rogelio1502')),
                    'security' => [
                        'protocol' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_PROTOCOL', 'SASL_SSL'),
                        'sasl_username' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_SASL_USERNAME', ''),
                        'sasl_password' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_SASL_PASSWORD', ''),
                        /*
                            * AWS MSK IAM SASL
                            * 'protocol' => 'MSK_IAM_SASL',
                            * 'aws_region' => env('AWS_REGION', ''),
                            * 'aws_key' => env('AWS_ACCESS_KEY_ID', ''),
                            * 'aws_secret' => env('AWS_SECRET_ACCESS_KEY', ''),
                            *
                            * PAINTEXT
                            * 'protocol' => 'PLAINTEXT',
                            * 'sasl_username' => env('UXMAL_BACKEND_CMD_BROKER_MSK_SASL_USERNAME', ''),
                            * 'sasl_password' => env('UXMAL_BACKEND_CMD_BROKER_MSK_SASL_PASSWORD', ''),
                            *
                        */
                    ],
                ],
                'handles' => [
                    'cmd'
                ]
            ],
        ],
    ]
];
```

### Explanation:

* **`name`**: The name of the application.
* **`prefix`**: A route prefix applied to all CBQ routes.
* **`subdomain`**: Used to define a specific subdomain for the API.
* **`cbq.controllerClass`**: Specifies the controller handling CBQ requests.
* **`broker.default`**: Contains configurations for the Kafka broker used by the application.
* **`broker.default.kafka`**: Includes Kafka-specific configurations like brokers, group ID, and security protocols.
* **`topics`**: Specifies the Kafka topics to listen to, separated by a pipe (`|`).

This configuration file ensures the proper setup of Kafka communication and enables dynamic route handling by CBQ.

## Routes Definition

To facilitate the dynamic registration of routes, the `AppServiceProvider` is used to scan and register commands and queries within a specified directory. This is achieved through the `app()->make` method as shown:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app()->make('Uxmal\Backend\Helpers\RegisterCmdQry')
            ->register(__DIR__.'/../Domains/Public', [
                'middleware' => [],
            ]
        );
    }
}
```

This setup works seamlessly with the `RegisterCommand` and `RegisterQuery` attributes to define routes dynamically. When a command or query is annotated, it is automatically registered as a route based on its attribute configuration, streamlining the process and reducing the need for manual route definitions in `web.php` or `api.php`.

## Working with Commands

Command classes are located in `app/Domains/Public/Product/Commands`. For instance, the `Create` command resides in `Create.php`. Each command is annotated with the `RegisterCommand` attribute, which defines the route path, HTTP method, and command name.

Here’s an example:

```php
<?php

namespace App\Domains\Public\Product\Commands;

...
use Uxmal\Backend\Attributes\RegisterCommand;
use Uxmal\Backend\Command\CommandBase;
...

#[RegisterCommand('/public/products', 'post', 'cmd.public.products.create.v1')]
class Create extends CommandBase
{
    public function handle(): array
    {
        ...
    }
}
```

### Key Details

* All commands extend the `CommandBase` class.
* Each command implements a `handle` method, which contains the logic for the respective action (e.g., creating, updating, or deleting a record).

Here’s a complete example of a command:

```php
<?php

namespace App\Domains\Public\Product\Commands;

use Exception;
use Uxmal\Backend\Attributes\RegisterCommand;
use Uxmal\Backend\Command\CommandBase;
use App\Models\Product;
use Uxmal\Backend\Exception\BackendCBQException;

#[RegisterCommand('/public/products', 'post', 'cmd.public.products.create.v1')]
class Create extends CommandBase
{
    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): array
    {
        try {
            if (!isset($this->payload['name']) || !isset($this->payload['price'])) {
                throw new BackendCBQException('Name and price are required');
            }

            $product = new Product();
            $product->name = $this->payload['name'];
            $product->price = $this->payload['price'];
            $product->save();

            return [
                'success' => true,
                'data' => $product,
            ];
        } catch (Exception $e) {
            dump($e->getMessage());

            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

After creating or modifying a command, verify its registration using:

```sh
php artisan route:list
```

You should see the new route listed, similar to:

```sh
POST       public/products ........... cmd.public.products.create.v1 ➔ Uxmal\Backend ➔ CBQToBrokerController
```

## Working with Queries

Query classes are located in `app/Domains/Public/Product/Queries`. Queries are responsible for retrieving data from the database and are similarly annotated with the `RegisterQuery` attribute. This annotation defines the route path and query name.

Here’s an example of a query:

```php
<?php

namespace App\Domains\Public\Product\Queries;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Uxmal\Backend\Attributes\RegisterQuery;

#[RegisterQuery('/public/products', name: 'qry.public.products.v1')]
class Index
{
    /**
     * Handle the query to retrieve all products.
     *
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(Product::all());
    }
}
```

### Key Details

* Query classes do not extend a base class; instead, they implement an `__invoke` method to handle the query logic.
* Queries focus solely on retrieving data, keeping them lightweight and efficient.

After creating or modifying a query, you can verify its registration using:

```sh
php artisan route:list
```

You should see the query route listed, similar to:

```sh
GET       public/products ........... qry.public.products.v1 ➔ App\Domains\Public\Product\Queries\Index
```

## Using the API

To test the `Index` query, make a `GET` request to:

```sh
http://localhost:8000/public/products
```

This will return a JSON response with all the products from the database. Queries are designed to be straightforward and efficient, ensuring quick data retrieval for your application.
