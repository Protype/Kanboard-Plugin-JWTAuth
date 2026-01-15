# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Kanboard-Plugin-JWTAuth is a Kanboard plugin that provides JWT (JSON Web Token) authentication for the Kanboard API. It uses dual token mode (access + refresh tokens).

## Commands

### Install Dependencies

```sh
composer install
```

### Run Tests

```sh
./vendor/bin/phpunit -c tests/phpunit.xml
```

Run specific test suite:
```sh
./vendor/bin/phpunit -c tests/phpunit.xml --testsuite "JWTAuth Unit Tests"
./vendor/bin/phpunit -c tests/phpunit.xml --testsuite "JWTAuth Integration Tests"
```

## Architecture

### Key Components

**Plugin.php** - Entry point that:
- Registers the settings route at `settings/jwtauth`
- Hooks templates and assets into Kanboard's layout
- Registers `JWTAuthProvider` when JWT is enabled
- Exposes JSON-RPC API methods: `getJWTPlugin`, `getJWTToken`, `refreshJWTToken`, `revokeJWTToken`, `revokeUserJWTTokens`, `revokeAllJWTTokens`

**Auth/JWTAuthProvider.php** - Implements `PasswordAuthenticationProviderInterface`:
- `generateToken()` - Creates JWT(s) with user claims
- `generateAccessToken()` / `generateRefreshToken()` - Dual token mode
- `refreshToken($refreshToken)` - Exchange refresh token for new tokens (token rotation)
- `revokeToken($token)` - Revoke a specific token
- `revokeAllTokens($userId)` - Revoke all tokens for a user
- `verifyToken($token)` - Validates JWT tokens using HS256 algorithm
- `authenticate()` - Called by Kanboard's auth manager

**Model/JWTRevokedTokenModel.php** - Token revocation storage:
- `add($jti, $userId, $tokenType, $expiresAt)` - Add revoked token
- `isRevoked($jti)` - Check if token is revoked
- `revokeAllByUser($userId)` - Revoke all user tokens

**Schema/** - Database schema migrations for token revocation table:
- `Sqlite.php`, `Mysql.php`, `Postgres.php` - Creates `jwt_revoked_tokens` table
- Schema is automatically loaded by Kanboard's plugin system on first use

### Dual Token Mode

The plugin always operates in dual token mode:

- **Access Token**: Default 3 days, used for API authentication
- **Refresh Token**: Default 30 days, used to obtain new access tokens

Token structure includes:
- `jti` - Unique token ID for revocation tracking
- `type` - Token type ('access' or 'refresh')
- Standard JWT claims (iss, aud, iat, nbf, exp, data)

### API Methods

| Method | Description |
|--------|-------------|
| `getJWTPlugin` | Get plugin info and available methods |
| `getJWTToken` | Get access + refresh tokens |
| `refreshJWTToken` | Exchange refresh token for new tokens (token rotation) |
| `revokeJWTToken` | Revoke a specific token |
| `revokeUserJWTTokens` | Revoke all tokens for a specific user (admin only) |
| `revokeAllJWTTokens` | Revoke all tokens in system (admin only) |

### API Testing

```sh
# Get JWT tokens (dual mode returns access_token + refresh_token)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getJWTToken", "id": 1}' \
  "http://localhost/jsonrpc.php"

# Use access token for API requests
curl -X POST -u "admin:ACCESS_TOKEN" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getAllProjects", "id": 1}' \
  "http://localhost/jsonrpc.php"

# Refresh token (returns new access_token + refresh_token)
curl -X POST -u "admin:access_token" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "refreshJWTToken", "params": ["REFRESH_TOKEN"], "id": 1}' \
  "http://localhost/jsonrpc.php"

# Revoke a token
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "revokeJWTToken", "params": {"token": "TOKEN_TO_REVOKE"}, "id": 1}' \
  "http://localhost/jsonrpc.php"
```

### Configuration Keys

Stored in Kanboard's config model:

**Basic Settings:**
- `jwt_enable` - Enable/disable JWT authentication
- `jwt_secret` - HS256 signing key (auto-generated if empty)
- `jwt_issuer` - Token issuer claim (defaults to application URL)
- `jwt_audience` - Token audience claim (defaults to application URL)

**Token Expiration:**
- `jwt_access_expiration` - Access token TTL (default: 259200 = 3 days)
- `jwt_refresh_expiration` - Refresh token TTL (default: 2592000 = 30 days)

### Dependencies

- PHP >= 7.2
- `firebase/php-jwt` ^6.0

Note: The plugin manually loads `vendor/autoload.php` in both `JWTAuthProvider.php` and `ConfigController.php` since Kanboard doesn't use Composer autoloading for plugins.
