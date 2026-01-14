# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Kanboard-Plugin-JWTAuth is a Kanboard plugin that provides JWT (JSON Web Token) authentication for the Kanboard API. It allows users to authenticate using JWT tokens instead of sending credentials with each request.

## Commands

### Install Dependencies

```sh
composer install
```

No build, test, or lint configuration exists in this project.

## Architecture

### Key Components

**Plugin.php** - Entry point that:
- Registers the settings route at `settings/jwtauth`
- Hooks templates and assets into Kanboard's layout
- Registers `JWTAuthProvider` when JWT is enabled (`jwt_enable` config)
- Exposes `getJWTToken` JSON-RPC API method

**Auth/JWTAuthProvider.php** - Implements `PasswordAuthenticationProviderInterface`:
- `generateToken()` - Creates JWT with user claims (iss, aud, exp, user data)
- `verifyToken($token)` - Validates JWT tokens using HS256 algorithm
- `authenticate()` - Called by Kanboard's auth manager
- `setPassword($jwtToken)` - Receives JWT via basic auth password field

**Controller/ConfigController.php** - Settings management:
- `generateSecret()` - Creates cryptographically secure random key using `openssl_random_pseudo_bytes`
- `show()` / `save()` - Render and persist settings form

### Authentication Flow

1. Client calls `getJWTToken` JSON-RPC method with basic auth credentials
2. Plugin returns signed JWT token containing user ID and username
3. Client uses JWT as password in subsequent basic auth requests (username must match token)
4. `JWTAuthProvider.authenticate()` validates token signature and username match

### API Testing

```sh
# Get JWT token (requires basic auth)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getJWTToken", "id": 1}' \
  "http://localhost/jsonrpc.php"

# Use JWT token (replace password with token)
curl -X POST -u "admin:YOUR_JWT_TOKEN" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getAllProjects", "id": 1}' \
  "http://localhost/jsonrpc.php"
```

### Configuration Keys

Stored in Kanboard's config model:
- `jwt_enable` - Enable/disable JWT authentication
- `jwt_secret` - HS256 signing key (auto-generated if empty)
- `jwt_issuer` - Token issuer claim (defaults to application URL)
- `jwt_audience` - Token audience claim (defaults to application URL)
- `jwt_expiration` - Token TTL in seconds (default: 259200 = 3 days)

### Dependencies

- PHP >= 7.2
- `firebase/php-jwt` ^5.0

Note: The plugin manually loads `vendor/autoload.php` in both `JWTAuthProvider.php` and `ConfigController.php` since Kanboard doesn't use Composer autoloading for plugins.
