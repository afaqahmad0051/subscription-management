# 📦 Subscription Management System

A Laravel-based subscription management system that handles user registration, login, subscription plans, auto-renewal, and a scheduled renewal processor.

---

## 🚀 Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/afaqahmad0051/subscription-management.git
cd subscription-management
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Copy Environment File and Set Application Key

```bash
php artisan key:generate
cp .env.example .env
```

### 4. Configure Environment

Update `.env` with your database, queue, and other environment settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Start the Development Server

```bash
php artisan serve
```

## ⚙️ Running Background Services

The application uses Laravel Scheduler and Queues for processing subscription renewals and reminders.

### Run Queue Worker

```bash
php artisan queue:listen
```

### Run Laravel Scheduler

```bash
php artisan schedule:work
```

> **Note**: Ensure both processes are running continuously. For production, use Supervisor or Laravel Horizon.

## 🔁 Subscription Renewal Processor

This system includes a scheduled Artisan command that handles renewal of subscriptions.

### Manual Execution (For Testing)

```bash
php artisan subscriptions:process
```

#### Example Output (When Renewals Exist)

```
Starting subscription processing...
Found 1 subscriptions expiring soon
Processing subscription ID: 2 (User: user1@example.com)
  → Renewal job queued

Processing Summary:
- Renewals queued: 1
- Reminders queued: 0
```

#### Example Output (No Renewals Found)

```
Starting subscription processing...
Found 0 subscriptions expiring soon

Processing Summary:
- Renewals queued: 0
- Reminders queued: 0
```

> 🛠 To simulate a renewal, set a subscriptions.end_date to a past date in the database.

## 🛠 Artisan Commands Summary

| Command                             | Description                            |
| ----------------------------------- | -------------------------------------- |
| `php artisan serve`                 | Run the local server                   |
| `php artisan queue:listen`          | Start the queue listener               |
| `php artisan schedule:work`         | Start Laravel scheduler                |
| `php artisan subscriptions:process` | Manually trigger subscription renewals |

## 🔐 API Routes

### Authentication Routes

```php
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});
```

### Protected Routes (Sanctum Authentication Required)

#### 👤 User Subscription Routes

```php
Route::prefix('user/subscriptions')->controller(UserSubscriptionController::class)->group(function () {
    Route::get('', 'index');
    Route::post('', 'subscribe');
    Route::delete('/{subscription}/cancel', 'cancel');
    Route::patch('/{subscription}/auto-renew', 'toggleAutoRenew');
    Route::get('/plans', 'plans');
});
```

#### 🛡 Admin Subscription Routes

```php
Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/statistics', 'statistics');
    Route::get('/{subscription}', 'show');
});
```

## 📌 Notes

-   The subscription renewal logic checks subscriptions that are expiring within the next 24 hours.
-   Auto-renewals are only processed if `auto_renew` is enabled on the subscription.
-   Email reminders and job queueing are handled automatically via Laravel queues.
-   Each login invalidates previous tokens to ensure one active session per user.
-   Admin routes are protected by policies (`viewAny`, `view`, etc.) and require admin privileges.

## 🧠 Assumptions

-   Sanctum is used for API authentication.
-   Email configuration is properly set up for sending notifications.
-   Queue system is set to database.
