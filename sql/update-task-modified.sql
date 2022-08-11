UPDATE task
SET modified = CURRENT_TIMESTAMP
WHERE id = $id;
