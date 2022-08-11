UPDATE task
SET is_deleted = $is_deleted,
    modified   = CURRENT_TIMESTAMP
WHERE id = $id;
