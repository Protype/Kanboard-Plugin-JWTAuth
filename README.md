# Kanboard-Plugin-JWTAuth

JWT authentication plugin for Kanboard API. Supports dual token mode (access + refresh) with token revocation.

## Installation

1. Extract to `plugins/JWTAuth` directory
2. Enable in **Settings > JWT Auth**

## Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| JWT Secret | Signing key (auto-generated if empty) | - |
| JWT Expiration | Legacy token TTL (seconds) | 259200 (3 days) |
| Access Token Expiration | Access token TTL (enables dual mode) | - |
| Refresh Token Expiration | Refresh token TTL | 2592000 (30 days) |

## API Methods

| Method | Description |
|--------|-------------|
| `getJWTToken` | Get token(s) with basic auth |
| `refreshJWTToken` | Exchange refresh token for new access token |
| `revokeJWTToken` | Revoke a specific token |
| `revokeAllJWTTokens` | Revoke all user tokens |

## Usage

### Get Token

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**Response (Dual Mode):**
```json
{"result": {"access_token": "...", "refresh_token": "..."}}
```

### Use Token

```sh
curl -u "admin:ACCESS_TOKEN" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### Refresh Token

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```

---

# Kanboard-Plugin-JWTAuth (繁體中文)

Kanboard API 的 JWT 認證外掛。支援雙 Token 模式（存取 + 刷新）與 Token 撤銷功能。

## 安裝

1. 解壓縮至 `plugins/JWTAuth` 目錄
2. 在 **設定 > JWT Auth** 中啟用

## 設定選項

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| JWT Secret | 簽名金鑰（空白時自動產生） | - |
| JWT Expiration | 傳統模式 Token 有效期（秒） | 259200 (3 天) |
| Access Token Expiration | 存取 Token 有效期（啟用雙 Token 模式） | - |
| Refresh Token Expiration | 刷新 Token 有效期 | 2592000 (30 天) |

## API 方法

| 方法 | 說明 |
|-----|------|
| `getJWTToken` | 使用基本認證取得 Token |
| `refreshJWTToken` | 用刷新 Token 換取新的存取 Token |
| `revokeJWTToken` | 撤銷指定 Token |
| `revokeAllJWTTokens` | 撤銷使用者所有 Token |

## 使用方式

### 取得 Token

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**回應（雙 Token 模式）：**
```json
{"result": {"access_token": "...", "refresh_token": "..."}}
```

### 使用 Token

```sh
curl -u "admin:ACCESS_TOKEN" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### 刷新 Token

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```
