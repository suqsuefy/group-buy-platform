# Group Buying Platform

## Overview
Group Buying is a marketplace for group-based purchasing in Egypt. The platform allows users to join a campaign to buy a product at a discounted price once the minimum number of participants is reached.

## Project Stack
- Laravel 11
- Flutter
- MySQL
- Firebase
- Laravel Sanctum
- Nginx + VPS deployment

## MVP Goals
- Login and registration
- Store creation
- Product management
- Campaign creation
- Join campaign flow
- Order creation
- Admin overview dashboard
- Soft launch in one city

## Deployment Overview
This project is designed for a simple production setup using a Linux VPS with Nginx and PHP-FPM.

## Production .env
```env
APP_NAME="Group Buying"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=group_buy
DB_USERNAME=app_user
DB_PASSWORD=StrongPassword123!

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

LOG_CHANNEL=stack
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@your-domain.com"
MAIL_FROM_NAME="Group Buying"
```

## Production Commands
```bash
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## Nginx Config
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/group-buying/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Laravel Sanctum Setup
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Firebase Setup for Mobile
1. Create Firebase project
2. Add Android app
3. Download google-services.json
4. Add to android/app/
5. Add dependencies in pubspec.yaml
6. Initialize Firebase in Flutter

## Flutter Firebase Setup
```yaml
dependencies:
  firebase_core: ^3.1.0
  firebase_messaging: ^15.1.0
```

```dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

Future<void> initFirebase() async {
  await Firebase.initializeApp();
  FirebaseMessaging messaging = FirebaseMessaging.instance;
  NotificationSettings settings = await messaging.requestPermission();

  if (settings.authorizationStatus == AuthorizationStatus.authorized) {
    final token = await messaging.getToken();
    print('FCM Token: $token');
  }
}
```

## Launch Readiness Checklist
- [ ] Laravel API works
- [ ] Database is migrated
- [ ] Seeders run
- [ ] Login works
- [ ] Register works
- [ ] Product listing works
- [ ] Campaign listing works
- [ ] User joins campaign
- [ ] Order creation works
- [ ] Admin dashboard works
- [ ] Firebase notifications configured
- [ ] Production server is deployed
- [ ] Domain is configured
- [ ] Support channel is ready
- [ ] Soft launch city is selected

## Soft Launch Strategy
- City: Cairo / Giza
- Category: home products
- Vendors: 5
- Products: 15–20
- Campaigns: 3–5
- Goal: 50 orders in first month
- Payment: cash on delivery first

## Notes
This file represents the production-ready setup direction for the Group Buying platform MVP.
