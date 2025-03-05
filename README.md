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

**Enable JWT Auth**: Check this box to enable JWT authentication.

**JWT Secret**: Enter a secret key for signing the JWT tokens. Leave it empty to generate a random secret automatically.

**JWT Issuer** (optional): Defines the entity issuing the JWT tokens. If left empty, the system will use the configured application URL. If no application URL is set, the current website URL will be used instead.

**JWT Audience** (optional): Specifies the intended recipients of the JWT tokens. If left empty, the system will follow the same logic as the Issuer.

**JWT Expiration (seconds)** (optional): Enter the expiration time for the JWT tokens in seconds. The default is 259200 seconds (3 days).

## Usage

