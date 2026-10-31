# جمعني

منصة عربية للشراء الجماعي (group buying) في السوق المصري.

## Overview
جمعني هو تطبيق عربي/منصة إلكترونية تسمح للمستخدمين بالانضمام إلى حملات شراء جماعي على منتجات محددة. كلما زاد عدد المشاركين، انخفض السعر. التطبيق يربط بين العميل والمورد عبر نموذج تجميع الطلبات.

## Stack
- Laravel 11
- MySQL
- Flutter (mobile app)
- Laravel Sanctum
- Firebase (notifications)

## MVP Scope
- login/register
- store creation
- product management
- campaign creation
- join campaign
- order creation
- admin overview
- basic dashboard

## Project flow
1. User registers
2. User sees active campaigns
3. User joins campaign
4. Campaign reaches min participants threshold
5. User creates order
6. Payment on delivery in MVP
7. Admin tracks order and campaign status

## Main API routes
- POST /api/register
- POST /api/login
- GET /api/products
- POST /api/products
- GET /api/campaigns
- POST /api/campaigns
- POST /api/campaigns/{id}/join
- POST /api/orders
- GET /api/orders/my
- GET /api/admin/overview

## Note
The repository is intentionally scaffolded as a real implementation foundation for a Laravel + Flutter group buying platform.
