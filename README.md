Project: final-symfony-project

This folder contains the minimal Symfony-compatible scaffolding to migrate the native PHP app into a clean Symfony 6.4 application.

What I added here:
- Doctrine `Entity` classes matching the native SQL schema under `src/Entity/`
- A small set of `DataFixtures` to seed basic languages and verdicts (see `src/DataFixtures`)
- A `migration-map.md` that documents how SQL tables map to entities and routes

Important: This scaffold is not a full Symfony installation. Please run the Symfony installer to create a proper project in this folder, then copy the `src/Entity` and `src/DataFixtures` files over.

Recommended commands (run from workspace root):
```bash
# create a fresh Symfony 6.4 webapp
symfony new final-symfony-project --version="6.4.*" --webapp

# move scaffold files into the new project (if you ran the command above)
# (the scaffold already exists in this repo under final-symfony-project/ )

# inside the new project: install doctrine and fixtures
cd final-symfony-project
composer require symfony/orm-pack symfony/maker-bundle doctrine/doctrine-fixtures-bundle

# configure your DATABASE_URL in .env or .env.local to point to your MySQL container

# create migrations and load fixtures
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

If you prefer, I can run the local scaffolding steps and merge these files into the generated Symfony app — tell me to proceed after the `symfony new` step runs on your machine.
