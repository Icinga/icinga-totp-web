-- SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

CREATE TABLE secret (
  username varchar(254) NOT NULL,
  secret   varchar(255) NOT NULL,
  ctime    bigint NOT NULL,

  CONSTRAINT pk_secret PRIMARY KEY (username)
);

CREATE TABLE totp_schema (
  id        serial,
  version   smallint NOT NULL,
  timestamp bigint NOT NULL,

  CONSTRAINT pk_totp_schema PRIMARY KEY (id),
  CONSTRAINT idx_totp_schema_version UNIQUE (version)
);

INSERT INTO totp_schema (version, timestamp) VALUES (1, EXTRACT(EPOCH from NOW()) * 1000);
