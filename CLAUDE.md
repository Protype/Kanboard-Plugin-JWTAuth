# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

KanproBridge is a multi-functional Kanboard plugin that provides:
- **JWT Authentication** for the Kanboard API (dual token mode: access + refresh tokens)
- **User Metadata** storage for custom key-value pairs per user
- **User Avatar** upload and retrieval via API
- **User Password** change and reset via API
- **User Profile** get and update personal profile fields via API
- **Project User** overrides getProjectUsers/getAssignableUsers to return full user objects with avatar support

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
./vendor/bin/phpunit -c tests/phpunit.xml --testsuite "KanproBridge Unit Tests"
./vendor/bin/phpunit -c tests/phpunit.xml --testsuite "KanproBridge Integration Tests"
```

## Architecture

### Directory Structure

```
KanproBridge/
├── Feature/
│   ├── JWTAuth/
│   │   ├── Provider.php           # JWT authentication provider
│   │   └── RevokedTokenModel.php  # Token revocation storage
│   ├── UserMetadata/
│   │   └── Model.php              # User metadata storage
│   ├── UserAvatar/
│   │   └── Model.php              # User avatar API
│   ├── UserPassword/
│   │   └── Model.php              # User password API
│   ├── UserProfile/
│   │   └── Model.php              # User profile API
│   └── ProjectUser/
│       └── Model.php              # Extended project user API
├── Controller/
│   └── ConfigController.php
├── Schema/
│   ├── Sqlite.php
│   ├── Mysql.php
│   └── Postgres.php
├── Template/config/
│   ├── settings.php               # Unified settings page
│   └── sidebar.php
├── Assets/
├── tests/
│   └── units/Feature/...
├── Plugin.php
└── composer.json
```

### Key Components

**Plugin.php** - Entry point that:
- Registers the settings route at `settings/kanprobridge`
- Hooks templates and assets into Kanboard's layout
- Registers `JWTAuthProvider` when JWT is enabled
- Registers User Metadata API when enabled
- Registers User Avatar API when enabled
- Registers User Password API when enabled
- Registers User Profile API when enabled
- Registers Project User API when enabled
- Exposes JSON-RPC API methods

**Feature/JWTAuth/Provider.php** - Implements `PasswordAuthenticationProviderInterface`:
- `generateToken()` - Creates JWT(s) with user claims
- `generateAccessToken()` / `generateRefreshToken()` - Dual token mode
- `refreshToken($refreshToken)` - Exchange refresh token for new tokens (token rotation)
- `revokeToken($token)` - Revoke a specific token
- `revokeAllTokens($userId)` - Revoke all tokens for a user
- `verifyToken($token)` - Validates JWT tokens using HS256 algorithm
- `authenticate()` - Called by Kanboard's auth manager

**Feature/JWTAuth/RevokedTokenModel.php** - Token revocation storage:
- `add($jti, $userId, $tokenType, $expiresAt)` - Add revoked token
- `isRevoked($jti)` - Check if token is revoked
- `revokeAllByUser($userId)` - Revoke all user tokens

**Feature/UserMetadata/Model.php** - User metadata storage:
- `getAll($userId)` - Get all metadata for a user
- `get($userId, $name, $default)` - Get specific metadata value
- `exists($userId, $name)` - Check if metadata exists
- `save($userId, $values)` - Save metadata key-value pairs
- `remove($userId, $name)` - Remove specific metadata entry

**Feature/UserAvatar/Model.php** - User avatar API:
- `upload($userId, $imageData)` - Upload avatar (base64 encoded)
- `get($userId)` - Get avatar (base64 encoded)
- `remove($userId)` - Remove avatar

**Feature/UserPassword/Model.php** - User password API:
- `change($currentPassword, $newPassword)` - Change own password (requires current)
- `reset($userId, $newPassword)` - Reset any user's password (admin only)

**Feature/UserProfile/Model.php** - User profile API:
- `get($userId)` - Get user profile data with avatar
- `update($userId, $values)` - Update profile fields (username, name, email, theme, timezone, language, filter)

**Feature/ProjectUser/Model.php** - Overrides Kanboard's project user API:
- `getProjectUsers($projectId)` - Get full user objects with avatar for project members (replaces `{userId: username}` mapping)
- `getAssignableUsers($projectId)` - Get full user objects with avatar for assignable users (excludes project-viewer)

**Schema/** - Database schema migrations:
- `version_1`: Creates `jwt_revoked_tokens` table
- `version_2`: Creates `kanpro_user_metadata` table

### Dual Token Mode

The JWT feature always operates in dual token mode:

- **Access Token**: Default 3 days, used for API authentication
- **Refresh Token**: Default 30 days, used to obtain new access tokens

Token structure includes:
- `jti` - Unique token ID for revocation tracking
- `type` - Token type ('access' or 'refresh')
- Standard JWT claims (iss, aud, iat, nbf, exp, data)

### User Metadata / Avatar / Profile Permissions

- Users can only access their own data
- Administrators can access any user's data
- All operations return `false` or `null` if access is denied

### API Methods

#### JWT Auth
| Method | Description | Permission |
|--------|-------------|------------|
| `getKanproBridgeStatus` | Get plugin info and available methods | Any user |
| `getJWTToken` | Get access + refresh tokens | Any user |
| `refreshJWTToken` | Exchange refresh token for new tokens | Any user |
| `revokeJWTToken` | Revoke a specific token | Own token only |
| `revokeUserJWTTokens` | Revoke all tokens for a user | Admin only |
| `revokeAllJWTTokens` | Revoke all tokens in system | Admin only |

#### User Metadata
| Method | Parameters | Permission |
|--------|------------|------------|
| `getUserMetadata` | `userId` | Self or admin |
| `getUserMetadataByName` | `userId`, `name`, `default` | Self or admin |
| `saveUserMetadata` | `userId`, `values` | Self or admin |
| `removeUserMetadata` | `userId`, `name` | Self or admin |

#### User Avatar
| Method | Parameters | Permission |
|--------|------------|------------|
| `uploadUserAvatar` | `userId`, `imageData` (base64) | Self or admin |
| `getUserAvatar` | `userId` | Self or admin |
| `removeUserAvatar` | `userId` | Self or admin |

#### User Password
| Method | Parameters | Permission |
|--------|------------|------------|
| `changeUserPassword` | `currentPassword`, `newPassword` | Self only |
| `resetUserPassword` | `userId`, `newPassword` | Admin only |

#### User Profile
| Method | Parameters | Permission |
|--------|------------|------------|
| `getUserProfile` | `userId` | Self or admin |
| `updateUserProfile` | `userId`, `values` | Self or admin |

#### Project User (overrides Kanboard built-in)
| Method | Parameters | Permission |
|--------|------------|------------|
| `getProjectUsers` | `projectId` | Any user |
| `getAssignableUsers` | `projectId` | Any user |

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

# User Metadata: Save
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "saveUserMetadata", "params": {"userId": 1, "values": {"theme": "dark"}}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Metadata: Get all
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getUserMetadata", "params": {"userId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Metadata: Get by name
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getUserMetadataByName", "params": {"userId": 1, "name": "theme"}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Metadata: Remove
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "removeUserMetadata", "params": {"userId": 1, "name": "theme"}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Avatar: Upload (base64 encoded image)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "uploadUserAvatar", "params": {"userId": 1, "imageData": "BASE64_ENCODED_IMAGE"}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Avatar: Get
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getUserAvatar", "params": {"userId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Avatar: Remove
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "removeUserAvatar", "params": {"userId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Password: Change own password
curl -X POST -u "user:password" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "changeUserPassword", "params": {"currentPassword": "oldpass", "newPassword": "newpass"}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Password: Reset (admin only)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "resetUserPassword", "params": {"userId": 1, "newPassword": "resetpass"}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Profile: Get (includes avatar)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getUserProfile", "params": {"userId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# User Profile: Update
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "updateUserProfile", "params": {"userId": 1, "values": {"name": "New Name", "theme": "dark", "timezone": "Asia/Taipei"}}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# Project User: Get all project members with full user data + avatar (overrides Kanboard built-in)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getProjectUsers", "params": {"projectId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"

# Project User: Get assignable users with full user data + avatar (overrides Kanboard built-in)
curl -X POST -u "admin:admin" -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getAssignableUsers", "params": {"projectId": 1}, "id": 1}' \
  "http://localhost/jsonrpc.php"
```

### Configuration Keys

Stored in Kanboard's config model:

**JWT Settings:**
- `jwt_enable` - Enable/disable JWT authentication
- `jwt_secret` - HS256 signing key (auto-generated if empty)
- `jwt_issuer` - Token issuer claim (defaults to application URL)
- `jwt_audience` - Token audience claim (defaults to application URL)
- `jwt_access_expiration` - Access token TTL (default: 259200 = 3 days)
- `jwt_refresh_expiration` - Refresh token TTL (default: 2592000 = 30 days)

**User Metadata Settings:**
- `kanpro_user_metadata_enable` - Enable/disable User Metadata API

**User Avatar Settings:**
- `kanpro_user_avatar_enable` - Enable/disable User Avatar API

**User Password Settings:**
- `kanpro_user_password_enable` - Enable/disable User Password API

**User Profile Settings:**
- `kanpro_user_profile_enable` - Enable/disable User Profile API

**Project User Settings:**
- `kanpro_project_user_enable` - Enable/disable Project User API

### Dependencies

- PHP >= 7.2
- `firebase/php-jwt` ^6.0

Note: The plugin manually loads `vendor/autoload.php` in both `Provider.php` and `ConfigController.php` since Kanboard doesn't use Composer autoloading for plugins.
