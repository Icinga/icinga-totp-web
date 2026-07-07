-- SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

CREATE TABLE secret (
  username varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  secret   varchar(255) NOT NULL,
  ctime    bigint NOT NULL,

  CONSTRAINT pk_secret PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

CREATE TABLE totp_schema (
  id        int unsigned NOT NULL AUTO_INCREMENT,
  version   smallint NOT NULL,
  timestamp bigint unsigned NOT NULL,

  CONSTRAINT pk_totp_schema PRIMARY KEY (id),
  CONSTRAINT idx_totp_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT INTO totp_schema (version, timestamp) VALUES (1, UNIX_TIMESTAMP() * 1000);
