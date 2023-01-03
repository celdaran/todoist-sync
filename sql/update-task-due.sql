UPDATE task
SET due_date         = '$due_date',
    due_is_recurring = $due_is_recurring,
    due_string       = '$due_string'
WHERE id = '$id';
