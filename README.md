# WooCommerce PHP SDK

A lightweight PHP SDK for interacting with the WooCommerce REST API.

## Requirements

- PHP 8.3 or higher
- A WooCommerce site with REST API enabled
- API consumer key and secret from WooCommerce

## Installation

Install via Composer:

```bash
composer require apiera/woo-php-sdk
```

## Basic Usage

```php
use Apiera\WooPhpSdk\Client;
use Apiera\WooPhpSdk\Configuration;
use Apiera\WooPhpSdk\Enum\ApiVersion;

// Create configuration
$config = new Configuration(
    baseUrl: 'https://your-store.com',
    consumerKey: 'your_consumer_key',
    consumerSecret: 'your_consumer_secret',
    apiVersion: ApiVersion::V3,
    userAgent: 'My Application/1.0',
    timeout: 30
);

// Initialize client
$client = new Client($config);

try {
    // Get all products
    $products = $client->get('products');

    // Create a product
    $newProduct = $client->post('products', [
        'name' => 'Test Product',
        'type' => 'simple',
        'regular_price' => '21.99'
    ]);

    // Update a product
    $updatedProduct = $client->put('products/123', [
        'name' => 'Updated Product Name'
    ]);

    // Delete a product
    $result = $client->delete('products/123');

} catch (\Apiera\WooPhpSdk\Exception\Http\HttpException $e) {
    $error = $e->getErrorMessage();
    echo sprintf(
        "Error: [%s] %s",
        $error->getCode(),
        $error->getMessage()
    );
}
```

## Error Handling

The SDK throws specialized exceptions for different HTTP error scenarios:

- `BadRequestException` - 400 errors
- `UnauthorizedException` - 401 errors
- `NotFoundException` - 404 errors
- `InternalServerErrorException` - 500 errors
- `RequestException` - Other HTTP errors

All exceptions extend from `HttpException` which provides helpful methods to access request/response details:

```php
try {
    $client->get('products/999');
} catch (\Apiera\WooPhpSdk\Exception\Http\HttpException $e) {
    echo $e->getRequestMethod(); // GET
    echo $e->getRequestUri(); // products/999
    echo $e->getResponseStatusCode(); // 404
    
    $error = $e->getErrorMessage();
    echo $error->getCode(); // not_found
    echo $error->getMessage(); // Product not found
    print_r($error->getData()); // ['status' => 404]
}
```

## Development

Run tests:
```bash
composer test
```

Run coding standards check:
```bash
composer cs:check
```

Run static analysis:
```bash
composer static:analyse
```

Run all checks:
```bash
composer check
```

## License

MIT License - see the [LICENSE](LICENSE.md) file for details.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.