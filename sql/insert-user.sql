INSERT INTO user (id,
                  email,
                  full_name,
                  inbox_project_id,
                  joined_at,
                  karma)
VALUES ($id,
        '$email',
        '$full_name',
        '$inbox_project_id',
        '$joined_at',
        $karma)
;
