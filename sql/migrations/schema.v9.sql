-- -----------------------------------------------
-- Clear out existing tables
-- -----------------------------------------------

DROP TABLE IF EXISTS label;
DROP TABLE IF EXISTS task;
DROP TABLE IF EXISTS task_label;
DROP TABLE IF EXISTS comment;

DROP TABLE IF EXISTS user_history;
DROP TABLE IF EXISTS import_history;
DROP TABLE IF EXISTS priority;

DROP TABLE IF EXISTS section;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS project;

DROP TABLE IF EXISTS sync_status;

-- -----------------------------------------------
-- Todoist Sync application helper tables
-- -----------------------------------------------

CREATE TABLE sync_status
(
  pk       INTEGER NOT NULL PRIMARY KEY,
  descr    TEXT    NOT NULL,
  created  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- For future use
INSERT INTO sync_status (descr)
VALUES ('Active');

CREATE TABLE user_history
(
  pk      INTEGER NOT NULL PRIMARY KEY,
  user_id TEXT NOT NULL,
  karma   REAL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user (id)
);

CREATE TABLE import_history
(
  pk       INTEGER NOT NULL PRIMARY KEY,
  batch    TEXT    NOT NULL,
  entity   TEXT    NOT NULL,
  success  INTEGER NOT NULL,
  inserted INTEGER NOT NULL,
  updated  INTEGER NOT NULL,
  deleted  INTEGER NOT NULL,
  created  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE priority
(
  pk             INTEGER NOT NULL PRIMARY KEY,
  description    TEXT    NOT NULL,
  created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO priority (description) VALUES ('Priority 4');
INSERT INTO priority (description) VALUES ('Priority 3');
INSERT INTO priority (description) VALUES ('Priority 2');
INSERT INTO priority (description) VALUES ('Priority 1');

-- -----------------------------------------------
-- Todoist tables
-- -----------------------------------------------

CREATE TABLE label
(
  pk             INTEGER NOT NULL PRIMARY KEY,

  id             TEXT NOT NULL UNIQUE,
  name           TEXT NOT NULL,
  color          TEXT,
  is_deleted     BOOLEAN,
  is_favorite    BOOLEAN,
  item_order     INTEGER,

  sync_status_pk INTEGER NOT NULL DEFAULT 1,
  created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE project
(
  pk             INTEGER NOT NULL PRIMARY KEY,

  id              TEXT NOT NULL UNIQUE,
  name            TEXT NOT NULL,
  child_order     INTEGER,
  collapsed       BOOLEAN,
  color           TEXT,
  inbox_project   BOOLEAN,
  is_archived     BOOLEAN,
  is_deleted      BOOLEAN,
  is_favorite     BOOLEAN,
  parent_id       BOOLEAN,
  shared          BOOLEAN,
  sync_id         TEXT,
  view_style      TEXT,

  sync_status_pk  INTEGER NOT NULL DEFAULT 1,
  created         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  last_synced     TIMESTAMP,
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE user
(
  pk                INTEGER NOT NULL PRIMARY KEY,

  id                TEXT NOT NULL UNIQUE,
  email             TEXT NOT NULL,
  full_name         TEXT NOT NULL,
  inbox_project_id  INTEGER,
  joined_at         TIMESTAMP,
  karma             REAL,

  sync_status_pk    INTEGER NOT NULL DEFAULT 1,
  created           TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inbox_project_id) REFERENCES project (id),
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE section
(
  pk              INTEGER NOT NULL PRIMARY KEY,

  id              TEXT UNIQUE,
  name            TEXT NOT NULL,
  added_at        TIMESTAMP,
  archived_at     TIMESTAMP,
  collapsed       BOOLEAN,
  is_archived     BOOLEAN,
  is_deleted      BOOLEAN,
  project_id      TEXT NOT NULL,
  section_order   INTEGER,
  sync_id         TEXT,
  user_id         TEXT,

  sync_status_pk  INTEGER NOT NULL DEFAULT 1,
  created         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES project (id),
  FOREIGN KEY (user_id) REFERENCES user (id),
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE task
(
  pk                INTEGER NOT NULL PRIMARY KEY,

  id                TEXT NOT NULL UNIQUE,
  added_at          TIMESTAMP NOT NULL,
  added_by_uid      TEXT,
  assigned_by_uid   TEXT,
  checked           BOOLEAN,
  collapsed         BOOLEAN,
  completed_at      TIMESTAMP NOT NULL,
  content           TEXT NOT NULL,
  description       TEXT,
  due_date          TEXT,
  due_is_recurring  BOOLEAN,
  due_string        TEXT,
  is_deleted        BOOLEAN,
  parent_id         TEXT,
  priority          INTEGER,
  project_id        TEXT,
  section_id        TEXT,
  sync_id           TEXT,
  user_id           TEXT NOT NULL,

  sync_status_pk   INTEGER NOT NULL DEFAULT 1,
  created          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  last_synced      TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES project (id),
  FOREIGN KEY (section_id) REFERENCES section (id),
  FOREIGN KEY (user_id) REFERENCES user (id),
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE task_label
(
  pk             INTEGER NOT NULL PRIMARY KEY,
  task_id        TEXT NOT NULL,
  label_id       TEXT NOT NULL,
  sync_status_pk INTEGER NOT NULL DEFAULT 1,
  created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (task_id, label_id),
  FOREIGN KEY (task_id) REFERENCES task (id),
  FOREIGN KEY (label_id) REFERENCES label (id),
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE comment
(
  pk              INTEGER NOT NULL PRIMARY KEY,

  id              TEXT NOT NULL UNIQUE,
  content         TEXT NOT NULL,
  is_deleted      BOOLEAN,
  item_id         TEXT NOT NULL,
  posted_at       TIMESTAMP,
  posted_uid      TEXT,

  sync_status_pk  INTEGER NOT NULL DEFAULT 1,
  created         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  modified        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES task (id),
  FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

-- data sanity checks

/*
SELECT * FROM sync_status;
SELECT * FROM user_history;
SELECT * FROM import_history;

SELECT * FROM label;
SELECT * FROM project;
SELECT * FROM user;
SELECT * FROM section;
SELECT * FROM task;
SELECT * FROM task_label;
SELECT * FROM comment;
*/
