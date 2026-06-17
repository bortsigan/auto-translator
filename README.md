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


## 5. Design choices

### Schema (scalable)
```
Database Schema

The database is designed to support large datasets and efficient searching.

languages stores supported locales like en, fr, es and can be extended without code changes.
translations stores translation keys and values per language.
tags and the tag_translation pivot table allow translations to be grouped by context like web, mobile, desktop.

Indexes are added to support fast lookups by language, key, and update time. 
MySQL Full Text indexes are used for content searches when available.
```

### Performance
```
The application is designed to handle 100,000+ translation records efficiently.

1. Database indexes are used for common search operations.
2. Search results are paginated to avoid loading large datasets into memory.
3. Translation exports are streamed using chunkById() to keep memory usage low.
4. Export responses support ETag-based validation, allowing users and CDNs to avoid downloading unchanged data.
```

### CDN Support
```
Export responses include standard cache headers (ETag, Last-Modified, and Cache-Control) making them compatible with CDNs such as Cloudflare, CloudFront, or Fastly.

This allows cached responses to be revalidated efficiently while ensuring clients always receive the latest translation data.
```

### Code structure

```
The application follows SOLID principles and keeps responsibilities separated:

1. Controllers handle HTTP requests and responses.
2. Services contain business logic.
3. Form Requests handle validation.
4. API Resources transform models into response payloads.
```

### Security

```
1. API access is protected using bearer tokens.
2. Tokens are stored as SHA256 hashes instead of using plain text.
3. All input is validated before processing.
4. Uses queries and Eloquent ORM to prevent SQL injections.
5. API responses return generic error messages and do not expose internal system details.
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