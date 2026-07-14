# Configuration

## Database Configuration

Connection configuration for the database, which
[Icinga TOTP Web](https://github.com/Icinga/icinga-totp-web) uses.

### Configure via Web UI

1. Create a new resource for the Icinga TOTP Web database via the
   `Configuration → Application → Resources` menu.
2. Configure the resource you just created as the database connection for
   Icinga TOTP Web using the `Configuration → Modules → totp → Database` menu.

### Configure manually

Alternatively you can configure the resource manually.

Create `/etc/icingaweb2/modules/totp/config.ini` and point the module at a
database resource defined in Icinga Web:

```ini
[database]
resource = "totp_mysql"
```

Define the resource in `/etc/icingaweb2/resources.ini` if it does not already exist.

```ini
[totp_mysql]
db = mysql
host = localhost
dbname = totp
username = totp
password = CHANGEME
```

## Module Configuration

The issuer shown in authenticator applications and the accepted clock drift can
be configured under `Configuration → Modules → totp → Configuration`. The defaults
are `icingaweb` and 10 seconds. Leeway must be between 0 and 29 seconds.
