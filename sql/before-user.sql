SELECT email,
       full_name,
       inbox_project_id,
       joined_at,
       karma
FROM user
WHERE id = '$id';
