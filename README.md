# DigitalTolk

---

A Laravel 13 project API for storing, searching, and exporting translations across locales and contexts like mobile, desktop, and web. It uses custom token based authenticatin, a MySQL schema indexed for fast read JSON export that's always updated. With unit and feature test coverage, an OpenAPI doc at /docs, and commands to setup the application.

---

## 1. Quick start (Docker)

```bash
# add .env
cp .env.example .env

# Build & start 
docker compose up -d --build --force-recreate

# Install PHP dependencies inside the container
docker compose exec app composer install

# App key & migrations & seed reference data
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh

# seed 100k translations
docker compose exec app php artisan translations:seed --count=100000
```

Services:

```
App         - http://localhost:8080   
API docs    - http://localhost:8080/docs   
phpMyAdmin  - http://localhost:8081  
MySQL       - localhost:3306 (`tms` / `tms_password`)    
```

Stop everything with `docker compose down -v`

## 2. Quick start (no Docker)

Requires PHP 8.4, MySQL 8.4 locally.

```bash
cp .env.example .env

composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## 3. Running tests

```bash
# All tests
A. docker compose exec app php artisan test
B. php artisan test
```

## 4. API overview

All `/api/*` routes need bearer token except for the exporter and auth endpoints

```http
Authorization: Bearer [token]

Auth
POST /api/auth/register 
POST /api/auth/login 
POST /api/auth/logout 

Languages
GET /api/languages 
POST /api/languages 

Tags
GET /api/tags 
POST /api/tags 

Translations
GET /api/translations?key=&content=&locale&per_page=
POST /api/translations 
GET /api/translations/{id} 
PUT /api/translations/{id} 
DELETE /api/translations/{id} 
GET /api/translations/export/{locale}

```


### FYI
```bash
# if you already executed | run the command
docker compose exec app php artisan translations:seed --count=100000
# and you re-run it again. make sure you execute | run the command 
docker compose exec app php artisan migrate:fresh  
# and then
docker compose exec app php artisan translations:seed --count=100000
```