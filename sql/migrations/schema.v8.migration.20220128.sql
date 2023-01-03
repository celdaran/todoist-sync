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
