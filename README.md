# Gestion des stages

Symfony 7.3 web app for managing internships. Four roles:

- **Visitor**: browse internship offers.
- **Student**: register, apply with a CV (PDF), track applications.
- **Company**: register, publish and manage offers, review applications.
- **Administrator**: manage users and offers, view statistics (charts).

Email notifications are sent through the Symfony Mailer (MailHog in development).

## Requirements

- PHP >= 8.2 (with `pdo_mysql`)
- Composer
- MySQL 8
- [MailHog](https://github.com/mailhog/MailHog) (optional, to see emails)
- [Symfony CLI](https://symfony.com/download) (optional)

## Setup

```bash
composer install
```

Create a `.env.local` file to override the defaults from `.env`:

```dotenv
APP_SECRET=any-random-string
DATABASE_URL="mysql://root:@127.0.0.1:3306/gestion_stages?serverVersion=8.0.32&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
```

Then create the database and run the app:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve        # or: php -S localhost:8000 -t public
```

MailHog UI: http://localhost:8025

The repo contains no seed data: register a student/company from the site. Administrators
must be inserted directly in the `administrateur` table (password hashed with
`php bin/console security:hash-password`).

## Tests

```bash
php bin/phpunit
```

## Notes

- Uploaded CVs are stored in `public/uploads/cv/` (git-ignored).
