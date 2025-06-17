# Olona Talents

## Project initialization
Run:
```
php bin/console app:init-project
```

## Create a moderator
Run:
```
php bin/console app:create-moderateur <email> <password>
```

## Temporary SSO flow
1. Users authenticate on `olona-talents.com`.
2. After login, the server generates a temporary token and redirects to the white label domain:
   `https://client1.olona-talents.com/consume-token?token=XYZ123`.
3. The client domain requests `/sso/verify-token` with the token to retrieve user info.
4. With the returned data, the user session is created on `client1`.
