SELECT
  due_date,
  due_is_recurring,
  due_string
FROM task
WHERE id = $id;
