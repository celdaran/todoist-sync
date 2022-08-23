SELECT
    task_id,
    project_id,
    content,
    is_deleted,
    posted
FROM comment
WHERE id = $id;
