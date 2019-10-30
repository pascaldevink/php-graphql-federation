# GraphQL Federation for PHP

Utility for creating GraphQL microservices, which can be combined into a single endpoint through tools like Apollo 
Gateway.

[![Build Status](https://travis-ci.org/pascaldevink/php-graphql-federation.svg?branch=master)](https://travis-ci.org/pascaldevink/php-graphql-federation)

## Installation

Use the package manager [composer](https://getcomposer.org) to install graphql-federation.

```bash
composer require pascaldevink/graphql-federation
```

## Usage

Assuming you already have an existing GraphQL implementation using [webonyx/graphql-php](https://github.com/webonyx/graphql-php), 
these commands add federation:

```php
# First, build your existing schema
$existingSchema = BuildSchema::build(file_get_contents(__DIR__ . '/schema.graphqls'));

# Then, extend it with Federation
$federation = new \PascalDeVink\GraphQLFederation\Federation();
$schema = $federation->extendSchema($existingSchema);

# Build your root value resolver
$rootValue = include __DIR__ . '/rootvalue.php';

# And extend it with Federation resolvers
$rootValue = $federation->addResolversToRootValue($rootValue);

# Finally, execute the query
GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
```

See [the example of webonyx/graphql-php](https://github.com/webonyx/graphql-php/tree/master/examples/02-shorthand)
for the rest of the code to make it work.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
