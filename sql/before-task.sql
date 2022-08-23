SELECT
    content,
    date_added,
    date_completed,
    description,
    has_more_notes,
    in_history,
    is_deleted,
    parent_id,
    priority,
    project_id,
    section_id,
    user_id
FROM task
WHERE id = $id;
