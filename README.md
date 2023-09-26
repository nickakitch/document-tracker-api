# Document Tracker

This is a Laravel application to serve as the backend for a document tracking application.

## Getting Started

1. Clone the repository
2. `composer install`
3. `cp .env.example .env`
4. `php artisan key:generate`
5. Configure a database and update the relevant connection variables in your `.env` file
6. Ensure the app is running at `api.app.document-tracker.test`
7. Run the migrations and seed `php artisan migrate:fresh --seed`
