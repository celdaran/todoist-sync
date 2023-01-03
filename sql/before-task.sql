SELECT content,
       added_at,
       added_by_uid,
       assigned_by_uid,
       checked,
       collapsed,
       completed_at,
       description,
       parent_id,
       priority,
       project_id,
       section_id,
       sync_id,
       user_id
FROM task
WHERE id = '$id';
