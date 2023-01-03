UPDATE user
SET email            = '$email',
    full_name        = '$full_name',
    inbox_project_id = '$inbox_project_id',
    joined_at        = '$joined_at',
    karma            = $karma
WHERE id = $id;
