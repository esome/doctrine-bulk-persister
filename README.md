# Doctrine Bulk Persister
This Library allows bulk-persisting entities with Doctrine. This by-passes many of Doctrine ORMs
features in return for high-performance bulk inserts (or updates).

## Development Setup
Start necessary Containers

    docker-compose up -d

Install dependencies

    docker/composer install

Run Unit-Tests

    docker/phpunit
