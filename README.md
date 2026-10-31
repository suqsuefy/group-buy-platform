# Group Buying

Group Buying Marketplace for Egypt.

## Overview
A marketplace for group-based purchasing, where users join campaigns to buy products at better prices as group demand grows.

## Purpose
The platform connects customers and vendors in a simple buying model:
- Users join campaigns
- Vendors create offers
- Customers get discounted pricing when group size increases
- The app earns a commission on each order

## MVP Scope
- User registration and login
- Store creation
- Product management
- Campaign creation
- Join campaign flow
- Order creation
- Cash on delivery in MVP
- Admin dashboard overview

## Stack
- Laravel 11
- MySQL
- Flutter
- Laravel Sanctum
- Firebase

## Main API Endpoints
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

## Project flow
1. User creates account
2. User browses campaigns
3. User joins campaign
4. Campaign reaches minimum number of participants
5. User places order
6. Order is fulfilled with cash on delivery in MVP

## Notes
This repo is an implementation foundation for a real group buying marketplace project.
