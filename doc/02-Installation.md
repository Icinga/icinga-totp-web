<!-- {% if index %} -->
# Installing Icinga TOTP Web

The recommended way to install Icinga TOTP Web is to use prebuilt packages for
all supported platforms from our official release repository. Please follow the
steps listed for your target operating system, which guide you through setting
up the repository and installing Icinga TOTP Web.

<!-- {% else %} -->
<!-- {% if not icingaDocs %} -->

## Installing the Package

If the [repository](https://packages.icinga.com) is not configured yet, please
add it first. Then use your distribution's package manager to install the
`icinga-totp-web` package or install [from source](02-Installation.md.d/From-Source.md).
<!-- {% endif %} -->

## Setting up the Database

A MySQL or PostgreSQL database is required to store TOTP secrets. Please follow
the steps listed for your target database.

### Setting up a MySQL Database

Set up a MySQL database for Icinga TOTP Web:

```
# mysql -u root -p

CREATE DATABASE totp;
CREATE USER 'totp'@'localhost' IDENTIFIED BY 'CHANGEME';
GRANT ALL ON totp.* TO 'totp'@'localhost';
```

Import the schema:

```bash
mysql -u totp -p totp < /usr/share/icingaweb2/modules/totp/schema/mysql/schema.sql
```

### Setting up a PostgreSQL Database

Allow authenticated local sessions for the `totp` database user by modifying
the `pg_hba.conf` file. Its location is operating-system specific, but can be
queried:

```bash
su postgres -c "psql -c 'show hba_file;'"
```

Add the following entries before any broader matching rules:

```
local totp totp              scram-sha-256
host  totp totp 127.0.0.1/32 scram-sha-256
host  totp totp      ::1/128 scram-sha-256
```

Use `md5` instead of `scram-sha-256` only with PostgreSQL versions older than 10.

For a remote database server, make sure PostgreSQL listens on an address
reachable from Icinga Web. Then add `host` entries before any broader matching
rules, only for the Icinga Web server addresses or subnets that should connect
to PostgreSQL. For example, if Icinga Web connects from `192.0.2.43`:

```
host  totp totp 192.0.2.43/32 scram-sha-256
```

To apply the changes, reload PostgreSQL:

```bash
systemctl reload postgresql
```

Now proceed with actually creating both user and database.

The example below uses the `en_US.UTF-8` locale. This locale must be available
on the PostgreSQL server. Use `locale -a` to list available locale names and
replace `en_US.UTF-8` with the exact UTF-8 locale name on your system, such
as `en_US.utf8`.

```
# su -l postgres

createuser -P totp
createdb -E UTF8 --locale en_US.UTF-8 -T template0 -O totp totp
```

Import the schema:

```bash
psql -U totp totp < /usr/share/icingaweb2/modules/totp/schema/pgsql/schema.sql
```

## Enabling the Module

Enable the module using the `icingacli`:

```bash
icingacli module enable totp
```

This concludes the installation. Now proceed with the
[configuration](03-Configuration.md).
<!-- {% endif %} --><!-- {# end else if index #} -->
