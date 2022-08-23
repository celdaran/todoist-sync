UPDATE task
SET content        = '$content',
    date_added     = '$date_added',
    date_completed = '$date_completed',
    description    = '$description',
    has_more_notes = $has_more_notes,
    in_history     = $in_history,
    is_deleted     = $is_deleted,
    parent_id      = $parent_id,
    priority       = $priority,
    project_id     = $project_id,
    section_id     = $section_id,
    user_id        = $user_id
WHERE id = $id;
