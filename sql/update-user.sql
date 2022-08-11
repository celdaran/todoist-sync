UPDATE user
SET email         = '$email',
    full_name     = '$full_name',
    inbox_project = $inbox_project,
    join_date     = '$join_date',
    karma         = $karma,
    modified      = CURRENT_TIMESTAMP
WHERE id = $id;
