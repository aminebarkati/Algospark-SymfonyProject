## Clone and run

Install dependencies:

```
composer install
```

Start local infra (MySQL):

```
docker compose up -d
```

This brings up the MySQL database.

Run migrations:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Load demo fixtures (optional):

```
php bin/console doctrine:fixtures:load --no-interaction
```

Start the web application (Symfony CLI recommended):

```
symfony serve -d
```

If you do not use the Symfony CLI, you can use the built-in PHP server:

```
php -S localhost:8000 -t public
```

Start the judge worker in a separate terminal:

```
php worker/worker.php
```

## Demo data

The fixtures include an admin account and demo users. The seeded admin password is adminpass.

## Main documentation

- Architecture: [docs/architecture.md](docs/architecture.md)
- Controller breakdown: [docs/code_break_down.md](docs/code_break_down.md)
- Worker judge guide: [docs/worker.md](docs/worker.md)
