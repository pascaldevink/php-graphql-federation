# Parsing GraphQL IDL shorthand

Shows how to build GraphQL schema from shorthand, wire up some resolvers, and add federation.

### Run locally
```
php -S localhost:8080 ./graphql.php
```

### Try query
```
curl http://localhost:8080 -d '{"query": "query { echo(message: \"Hello World\") }" }'
```

### Try mutation
```
curl http://localhost:8080 -d '{"query": "mutation { sum(x: 2, y: 2) }" }'
```

### Try service sdl
```
curl http://localhost:8080 -d '{"query": "query {  _service {    sdl  } }" }'
```

### Try entities
curl http://localhost:8080 -d '{"query":"query { _entities(representations: [{ __typename:\"User\", id:\"2\"}]) { __typename ... on User { id } } }","variables":{"_representations":[{"__typename":"User","id":"2"}]}}'
