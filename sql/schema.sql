-- -----------------------------------------------
-- Clear out existing tables
-- -----------------------------------------------

DROP TABLE IF EXISTS sync_status;
DROP TABLE IF EXISTS user_history;
DROP TABLE IF EXISTS import_history;

DROP TABLE IF EXISTS label;
DROP TABLE IF EXISTS project;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS section;
DROP TABLE IF EXISTS task;
DROP TABLE IF EXISTS task_label;
DROP TABLE IF EXISTS comment;

DROP TRIGGER IF EXISTS update_label_modified;
DROP TRIGGER IF EXISTS update_project_modified;
DROP TRIGGER IF EXISTS update_user_modified;
DROP TRIGGER IF EXISTS update_section_modified;
DROP TRIGGER IF EXISTS update_task_modified;
DROP TRIGGER IF EXISTS update_task_label_modified;
DROP TRIGGER IF EXISTS update_comment_modified;


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
    user_id INTEGER NOT NULL,
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

    id             INTEGER NOT NULL UNIQUE,
    name           TEXT    NOT NULL,
    color          INTEGER,
    is_deleted     INTEGER,
    is_favorite    INTEGER,
    task_order     INTEGER,

    display_name   TEXT NULL,
    sync_status_pk INTEGER NOT NULL DEFAULT 1,
    created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE project
(
    pk             INTEGER NOT NULL PRIMARY KEY,

    id             INTEGER NOT NULL UNIQUE,
    name           TEXT    NOT NULL,
    child_order    INTEGER,
    collapsed      INTEGER,
    color          INTEGER,
    has_more_notes INTEGER,
    inbox_project  INTEGER,
    is_archived    INTEGER,
    is_deleted     INTEGER,
    is_favorite    INTEGER,
    parent_id      INTEGER,

    sync_status_pk INTEGER NOT NULL DEFAULT 1,
    created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE user
(
    pk             INTEGER NOT NULL PRIMARY KEY,

    id             INTEGER NOT NULL UNIQUE,
    email          TEXT    NOT NULL,
    full_name      TEXT    NOT NULL,
    inbox_project  INTEGER,
    join_date      TEXT,
    karma          REAL,

    sync_status_pk INTEGER NOT NULL DEFAULT 1,
    created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inbox_project) REFERENCES project (id),
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE section
(
    pk             INTEGER NOT NULL PRIMARY KEY,
    id             INTEGER NOT NULL UNIQUE,
    name           TEXT    NOT NULL,
    project_id     INTEGER NOT NULL,
    collapsed      INTEGER,
    date_added     TEXT,
    date_archived  TEXT,
    is_archived    INTEGER,
    is_deleted     INTEGER,
    section_order  INTEGER,
    user_id        INTEGER,
    sync_status_pk INTEGER NOT NULL DEFAULT 1,
    created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES project (id),
    FOREIGN KEY (user_id) REFERENCES user (id),
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE task
(
    pk               INTEGER NOT NULL PRIMARY KEY,
    id               INTEGER NOT NULL UNIQUE,
    content          TEXT    NOT NULL,
    date_added       TEXT    NOT NULL,
    date_completed   TEXT    NOT NULL,
    description      TEXT,
    due_date         TEXT,
    due_is_recurring INTEGER,
    due_string       TEXT,
    has_more_notes   INTEGER,
    in_history       INTEGER,
    is_deleted       INTEGER,
    parent_id        INTEGER,
    priority         INTEGER,
    project_id       INTEGER,
    section_id       INTEGER,
    user_id          INTEGER NOT NULL,
    sync_status_pk   INTEGER NOT NULL DEFAULT 1,
    created          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES project (id),
    FOREIGN KEY (section_id) REFERENCES section (id),
    FOREIGN KEY (user_id) REFERENCES user (id),
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TABLE task_label
(
    pk             INTEGER NOT NULL PRIMARY KEY,
    task_id        INTEGER NOT NULL,
    label_id       INTEGER NOT NULL,
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
    pk             INTEGER NOT NULL PRIMARY KEY,
    id             INTEGER NOT NULL UNIQUE,
    task_id        INTEGER NOT NULL,
    project_id     INTEGER,
    content        TEXT    NOT NULL,
    is_deleted     INTEGER,
    posted         TEXT,
    sync_status_pk INTEGER NOT NULL DEFAULT 1,
    created        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    modified       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES task (id),
    FOREIGN KEY (project_id) REFERENCES project (id),
    FOREIGN KEY (sync_status_pk) REFERENCES sync_status (pk)
);

CREATE TRIGGER update_label_modified
    AFTER UPDATE
    ON label
    FOR EACH ROW
BEGIN
    UPDATE label
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (name <> old.name
        OR color <> old.color
        OR is_deleted <> old.is_deleted
        OR is_favorite <> old.is_favorite
        OR task_order <> old.task_order
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_project_modified
    AFTER UPDATE
    ON project
    FOR EACH ROW
BEGIN
    UPDATE project
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (name <> old.name
        OR child_order <> old.child_order
        OR collapsed <> old.collapsed
        OR color <> old.color
        OR has_more_notes <> old.has_more_notes
        OR inbox_project <> old.inbox_project
        OR is_archived <> old.is_archived
        OR is_deleted <> old.is_deleted
        OR is_favorite <> old.is_favorite
        OR parent_id <> old.parent_id
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_user_modified
    AFTER UPDATE
    ON user
    FOR EACH ROW
BEGIN
    UPDATE user
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (email <> old.email
        OR full_name <> old.full_name
        OR inbox_project <> old.inbox_project
        OR join_date <> old.join_date
        OR karma <> old.karma
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_section_modified
    AFTER UPDATE
    ON section
    FOR EACH ROW
BEGIN
    UPDATE section
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (name <> old.name
        OR project_id <> old.project_id
        OR collapsed <> old.collapsed
        OR date_added <> old.date_added
        OR date_archived <> old.date_archived
        OR is_archived <> old.is_archived
        OR is_deleted <> old.is_deleted
        OR section_order <> old.section_order
        OR user_id <> old.user_id
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_task_modified
    AFTER UPDATE
    ON task
    FOR EACH ROW
BEGIN
    UPDATE task
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (content <> old.content
        OR date_completed <> old.date_completed
        OR description <> old.description
        OR due_date <> old.due_date
        OR due_is_recurring <> old.due_is_recurring
        OR due_string <> old.due_string
        OR has_more_notes <> old.has_more_notes
        OR in_history <> old.in_history
        OR is_deleted <> old.is_deleted
        OR parent_id <> old.parent_id
        OR priority <> old.priority
        OR project_id <> old.project_id
        OR section_id <> old.section_id
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_task_label_modified
    AFTER UPDATE
    ON task_label
    FOR EACH ROW
BEGIN
    UPDATE task_label
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (task_id <> old.task_id
        OR label_id <> old.label_id
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

CREATE TRIGGER update_comment_modified
    AFTER UPDATE
    ON comment
    FOR EACH ROW
BEGIN
    UPDATE comment
    SET modified = CURRENT_TIMESTAMP
    WHERE pk = old.pk
      AND (task_id <> old.task_id
        OR project_id <> old.project_id
        OR content <> old.content
        OR is_deleted <> old.is_deleted
        OR posted <> old.posted
        OR sync_status_pk <> old.sync_status_pk
        )
END
;

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
