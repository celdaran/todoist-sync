SELECT
    email,
    full_name,
    inbox_project,
    join_date,
    karma
FROM user
WHERE id = $id;
