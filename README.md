# Icinga TOTP Web

Icinga TOTP Web adds TOTP (Time-based One-Time Password) two-factor
authentication to Icinga Web. Users enroll by scanning a QR code with any
RFC 6238-compatible authenticator app, then enter a 6-digit token on each login.

## Features

* Users enroll by scanning a QR code or entering the secret manually.
* Works with any RFC 6238-compatible authenticator app.
* TOTP secrets are stored per user in a MySQL or PostgreSQL database.
* Enrolled users must enter a 6-digit token on every login.

## Documentation

Icinga TOTP Web documentation is available at
[icinga.com/docs](https://icinga.com/docs/icinga-totp-web/latest/).

## License

Icinga TOTP Web and this documentation are licensed under the terms of the
[GNU General Public License Version 3](LICENSE.md).
