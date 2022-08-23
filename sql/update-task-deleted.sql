UPDATE task
SET is_deleted = $is_deleted
WHERE id = $id;
