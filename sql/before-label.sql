SELECT
    name,
    color,
    is_deleted,
    is_favorite,
    task_order
FROM label
WHERE id = $id;
