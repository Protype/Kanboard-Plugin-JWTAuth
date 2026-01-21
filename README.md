# KanproBridge

Multi-functional Kanboard plugin providing JWT authentication, User Metadata storage, and User Avatar API.

## Features

- **JWT Authentication**: Dual token mode (access + refresh) with token revocation
- **User Metadata**: Custom key-value storage per user
- **User Avatar**: Upload and retrieve avatars via API

## Installation

1. Extract to `plugins/KanproBridge` directory
2. Enable features in **Settings > KanproBridge**

## Configuration

### JWT Settings

| Setting | Description | Default |
|---------|-------------|---------|
| JWT Secret | Signing key (auto-generated if empty) | - |
| Access Token Expiration | Access token TTL (seconds) | 259200 (3 days) |
| Refresh Token Expiration | Refresh token TTL (seconds) | 2592000 (30 days) |

### User Metadata Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Metadata | Enable/disable User Metadata API | Disabled |

### User Avatar Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Avatar | Enable/disable User Avatar API | Disabled |

## API Methods

### JWT Authentication

| Method | Permission | Description |
|--------|------------|-------------|
| `getKanproBridgePlugin` | Any user | Get plugin info and available methods |
| `getJWTToken` | Any user | Get token(s) with basic auth |
| `refreshJWTToken` | Any user | Exchange refresh token for new access token |
| `revokeJWTToken` | Any user | Revoke own token only |
| `revokeUserJWTTokens` | Admin | Revoke all tokens for a specific user |
| `revokeAllJWTTokens` | Admin | Revoke all tokens in system |

### User Metadata

| Method | Permission | Description |
|--------|------------|-------------|
| `getUserMetadata` | Self or Admin | Get all metadata for a user |
| `getUserMetadataByName` | Self or Admin | Get a specific metadata value |
| `saveUserMetadata` | Self or Admin | Save metadata key-value pairs |
| `removeUserMetadata` | Self or Admin | Remove a metadata entry |

### User Avatar

| Method | Permission | Description |
|--------|------------|-------------|
| `uploadUserAvatar` | Self or Admin | Upload avatar (base64 PNG/JPG/GIF) |
| `getUserAvatar` | Self or Admin | Get avatar (base64) |
| `removeUserAvatar` | Self or Admin | Remove avatar |

## Usage

### Get Plugin Info

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getKanproBridgePlugin","id":1}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": {
    "name": "KanproBridge",
    "version": "2.1.0",
    "description": "Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems",
    "features": {...}
  }
}
```

### Get Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### Use Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### Refresh Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```

**Response (Token Rotation):**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### Revoke Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeJWTToken","id":1,"params":["TOKEN_TO_REVOKE"]}' \
  http://localhost/jsonrpc.php
```

### Save User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"saveUserMetadata","id":1,"params":{"userId":1,"values":{"theme":"dark"}}}' \
  http://localhost/jsonrpc.php
```

### Get User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserMetadata","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### Upload User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"uploadUserAvatar","id":1,"params":{"userId":1,"imageData":"BASE64_IMAGE"}}' \
  http://localhost/jsonrpc.php
```

### Get User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserAvatar","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

## Troubleshooting

### "Method not found" Error

API methods require their feature to be enabled first. If you see:

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**Solution:** Enable the feature in **Settings > KanproBridge** and save.

---

# KanproBridge (繁體中文)

多功能 Kanboard 外掛，提供 JWT 認證、使用者 Metadata 儲存與頭像 API。

## 功能

- **JWT 認證**：雙 Token 模式（存取 + 刷新）與 Token 撤銷
- **User Metadata**：使用者自訂鍵值對儲存
- **User Avatar**：透過 API 上傳與取得頭像

## 安裝

1. 解壓縮至 `plugins/KanproBridge` 目錄
2. 在 **設定 > KanproBridge** 中啟用功能

## 設定選項

### JWT 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| JWT Secret | 簽名金鑰（空白時自動產生） | - |
| Access Token Expiration | 存取 Token 有效期（秒） | 259200 (3 天) |
| Refresh Token Expiration | 刷新 Token 有效期（秒） | 2592000 (30 天) |

### User Metadata 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Metadata | 啟用/停用 User Metadata API | 停用 |

### User Avatar 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Avatar | 啟用/停用 User Avatar API | 停用 |

## API 方法

### JWT 認證

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getKanproBridgePlugin` | 任何用戶 | 取得外掛資訊與可用方法清單 |
| `getJWTToken` | 任何用戶 | 使用基本認證取得 Token |
| `refreshJWTToken` | 任何用戶 | 用刷新 Token 換取新的存取 Token |
| `revokeJWTToken` | 任何用戶 | 僅能撤銷自己的 Token |
| `revokeUserJWTTokens` | 管理員 | 撤銷指定用戶的所有 Token |
| `revokeAllJWTTokens` | 管理員 | 撤銷系統所有 Token |

### User Metadata

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getUserMetadata` | 本人或管理員 | 取得使用者所有 Metadata |
| `getUserMetadataByName` | 本人或管理員 | 取得指定 Metadata 值 |
| `saveUserMetadata` | 本人或管理員 | 儲存 Metadata 鍵值對 |
| `removeUserMetadata` | 本人或管理員 | 移除 Metadata 項目 |

### User Avatar

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `uploadUserAvatar` | 本人或管理員 | 上傳頭像（base64 PNG/JPG/GIF） |
| `getUserAvatar` | 本人或管理員 | 取得頭像（base64） |
| `removeUserAvatar` | 本人或管理員 | 移除頭像 |

## 使用方式

### 取得外掛資訊

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getKanproBridgePlugin","id":1}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": {
    "name": "KanproBridge",
    "version": "2.1.0",
    "description": "Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems",
    "features": {...}
  }
}
```

### 取得 Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### 使用 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### 刷新 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```

**回應（Token Rotation）：**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### 撤銷 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeJWTToken","id":1,"params":["TOKEN_TO_REVOKE"]}' \
  http://localhost/jsonrpc.php
```

### 儲存 User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"saveUserMetadata","id":1,"params":{"userId":1,"values":{"theme":"dark"}}}' \
  http://localhost/jsonrpc.php
```

### 取得 User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserMetadata","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### 上傳 User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"uploadUserAvatar","id":1,"params":{"userId":1,"imageData":"BASE64_IMAGE"}}' \
  http://localhost/jsonrpc.php
```

### 取得 User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserAvatar","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

## 疑難排解

### 「Method not found」錯誤

API 方法需要先啟用對應功能。如果你看到：

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**解決方案：** 在 **設定 > KanproBridge** 中啟用功能並儲存。
