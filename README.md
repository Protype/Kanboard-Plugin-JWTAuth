# Kanboard-Plugin-JWTAuth

This plugin provides JWT (JSON Web Token) authentication for the Kanboard API. It allows users to authenticate using JWT tokens, enhancing the security and flexibility of the authentication process.

## Installation

1. Download the latest release of the plugin from the [releases page](https://github.com/Protype/Kanboard-Plugin-JWTAuth/releases).

2. Extract the downloaded archive into the `plugins` directory of your Kanboard installation:
    ```sh
    cd /path/to/kanboard/plugins
    unzip JWTAuth.zip
    ```

3. Navigating to the JWT settings page in Kanboard and enabling the JWTAuth plugin.

## Configuration

- **Enable JWT Auth**: Check this box to enable JWT authentication.

- **JWT Secret**: Enter a secret key for signing the JWT tokens. Leave it empty to generate a random secret automatically.

- **JWT Issuer** (optional): Defines the entity issuing the JWT tokens. If left empty, the system will use the configured application URL. If no application URL is set, the current website URL will be used instead.

- **JWT Audience** (optional): Specifies the intended recipients of the JWT tokens. If left empty, the system will follow the same logic as the Issuer.

- **JWT Expiration** (optional): Enter the expiration time for the JWT tokens in seconds. The default is 259200 seconds (3 days).

## Usage

### Obtaining a JWT Token

To authenticate using JWT, first obtain a token by calling the `getJWTToken` API method. Use your Kanboard user credentials (e.g., the default `admin` / `admin`) to request the token.

Example request using `curl`:

```sh
curl -X POST \
  -u "admin:admin" \
  -H "Content-Type: application/json" \
  -d '{}' \
  "http://your-kanboard-instance/jsonrpc.php"
```

Example JSON payload:

```json
{
  "jsonrpc": "2.0",
  "method": "getJWTToken",
  "id": 1
}
```

Example response:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": "your-jwt-token"
}
```

The `result` field contains the JWT token, which should be used for subsequent API requests.

### Using the JWT Token for API Requests

Once you have obtained the token, use it as the password field in API calls. Instead of sending a username and password, replace the password field with the JWT token.

Example request to fetch the list of projects:

```sh
curl -X POST \
  -u "admin:your-jwt-token" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "getAllProjects", "id": 1}' \
  "http://your-kanboard-instance/jsonrpc.php"
```

Example response:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": [
    {
      "id": "1",
      "name": "Project A"
    },
    {
      "id": "2",
      "name": "Project B"
    }
  ]
}
```

With this approach, you no longer need to send your actual password with each API request, improving security while maintaining ease of use.

