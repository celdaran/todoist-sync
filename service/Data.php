<?php namespace App\Service;

use SQLite3;

/**
 * Data wrapper
 */
class Data
{
    private SQLite3 $db;

    public function __construct()
    {
        $db = $_ENV['TODOIST_DATABASE'];
        $this->db = new SQLite3($db);
    }

    //------------------------------------------------------------------
    // Return table data
    //------------------------------------------------------------------

    /**
     * @return array
     */
    public function getProjects(): array
    {
        return $this->getRows('project');
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->getRows('section');
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->getRows('task');
    }

    /**
     * @param int $taskId
     * @return array
     */
    public function getComments(int $taskId): array
    {
        return $this->getRows('', 'SELECT * FROM comment WHERE task_id = ' . $taskId);
    }

    /**
     * @param int $taskId
     * @return array
     */
    public function getTaskLabels(int $taskId): array
    {
        return $this->getRows('', 'SELECT * FROM task_label WHERE task_id = ' . $taskId);
    }

    //------------------------------------------------------------------
    // Primary update methods
    //------------------------------------------------------------------

    /**
     * @param array $label
     * @return Result
     */
    public function updateLabel(array $label): Result
    {
        return $this->execUpsert('label', $label);
    }

    /**
     * @param array $project
     * @return Result
     */
    public function updateProject(array $project): Result
    {
        return $this->execUpsert('project', $project);
    }

    /**
     * @param array $user
     * @return Result
     */
    public function updateUser(array $user): Result
    {
        return $this->execUpsert('user', $user);
    }

    /**
     * @param array $user
     * @return Result
     */
    public function insertUserHistory(array $user): Result
    {
        return $this->execInsert('user-history', $user);
    }

    /**
     * @param array $section
     * @return Result
     */
    public function updateSection(array $section): Result
    {
        return $this->execUpsert('section', $section);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTask(array $task): Result
    {
        return $this->execUpsert('task', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTaskDue(array $task): Result
    {
        return $this->execUpsert('task-due', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTaskDeleted(array $task): Result
    {
        return $this->execUpsert('task-deleted', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function deleteTaskLabel(array $task): Result
    {
        return $this->execDelete('task-label', $task);
    }

    /**
     *
     * @param array $task
     * @return Result
     */
    public function insertTaskLabel(array $task): Result
    {
        return $this->execInsert('task-label', $task);
    }

    /**
     * @param array $import
     * @return Result
     */
    public function insertImportHistory(array $import): Result
    {
        return $this->execInsert('import-history', $import);
    }

    /**
     * @param array $comment
     * @return Result
     */
    public function updateComment(array $comment): Result
    {
        return $this->execUpsert('comment', $comment);
    }

    /**
     * @param string $table
     * @param int $id
     */
    public function setLastSynced(string $table, int $id)
    {
        $updateLastSynced = "UPDATE $table SET last_synced = CURRENT_TIMESTAMP WHERE id = $id";
        $this->db->exec($updateLastSynced);
    }

    //------------------------------------------------------------------
    // Internal sqlite data access layer
    //------------------------------------------------------------------

    /**
     * @param string $table
     * @param ?string $query
     * @return array
     */
    private function getRows(string $table, ?string $query = null): array
    {
        $rows = [];

        if ($query === null) {
            $query = 'SELECT * FROM ' . $table;
        }

        $result = $this->db->query($query);

        $row = true;
        while ($row !== false) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row !== false) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     */
    private function execUpsert(string $table, array $row): Result
    {
        $result = new Result();

        // Get a before snapshot
        $beforeSql = $this->doSubs($this->getSql($table, 'before'), $row);
        if ($beforeSql <> 'QUERY-NOT-FOUND') {
            $beforeRow = $this->db->query($beforeSql);
            $beforeRow = $beforeRow->fetchArray(SQLITE3_ASSOC);
        } else {
            $beforeRow = [];
        }

        // Get the update statement
        $sql = $this->doSubs($this->getSql($table, 'update'), $row);

        // Run it and check results
        $succeeded = $this->db->exec($sql);
        if ($succeeded) {

            // If it succeeded, find out how many rows were affected
            $changes = $this->db->querySingle('SELECT changes()');

            if ($changes === 0) {
                // If no rows, then nothing updated, so let's insert
                $sql = $this->doSubs($this->getSql($table, 'insert'), $row);
                $succeeded = $this->db->exec($sql);
                if ($succeeded) {
                    $result->setSucceeded();
                    $result->setInserted();
                    $result->setMessage("Inserted $table row {$row['id']}");
                } else {
                    $msg = $this->db->lastErrorMsg();
                    $result->setMessage("Insert failed: $msg");
                }

            } else {
                if ($changes === 1) {
                    // If one change, the upsert succeeded, but we don't know if
                    // anything ACTUALLY updated. So lets compare the row's before
                    // data to the current $row data and decide how to proceed

                    if ($this->rowChanged($beforeRow, $row)) {
                        // If the row TRULY changed, then run a special update for the modified row
                        $updateModified = sprintf('UPDATE %s SET modified = CURRENT_TIMESTAMP WHERE id = %d', $table, $row['id']);
                        $this->db->exec($updateModified);

                        // And then react accordingly
                        $changes = $this->db->querySingle('SELECT changes()');
                        if ($changes === 1) {
                            $result->setSucceeded();
                            $result->setUpdated();
                            $result->setMessage("Updated $table row {$row['id']}");
                        } else {
                            $msg = $this->db->lastErrorMsg();
                            $result->setMessage("Unexpected value returned from changes(): $changes, msg: $msg");
                        }
                    }
                } else {
                    // Otherwise, this is alarming
                    $msg = $this->db->lastErrorMsg();
                    $result->setMessage("Unexpected value returned from changes(): $changes, msg: $msg");
                }
            }

            // The update itself didn't work
        } else {
            $msg = $this->db->lastErrorMsg();
            $result->setMessage("Error running initial update: $msg");
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     * @noinspection PhpSameParameterValueInspection
     */
    private function execDelete(string $table, array $row): Result
    {
        $result = new Result();

        $sql = $this->doSubs($this->getSql($table, 'delete'), $row);

        $succeeded = $this->db->exec($sql);
        if ($succeeded) {
            $result->setSucceeded();
            $result->setDeleted();
            $result->setMessage("Rows deleted");
        } else {
            $msg = $this->db->lastErrorMsg();
            $result->setMessage("Error executing delete: $msg");
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     */
    private function execInsert(string $table, array $row): Result
    {
        $result = new Result();

        $sql = $this->doSubs($this->getSql($table, 'insert'), $row);

        $succeeded = $this->db->exec($sql);
        if ($succeeded) {
            $result->setSucceeded();
            $result->setInserted();
            $result->setMessage("Rows inserted");
        } else {
            $msg = $this->db->lastErrorMsg();
            $result->setMessage("Error executing delete: $msg");
        }

        return $result;
    }

    //------------------------------------------------------------------
    // File I/O
    //------------------------------------------------------------------

    private function getSql(string $object, string $verb) : string
    {
        return file_get_contents(__DIR__."/../sql/$verb-$object.sql");
    }

    //------------------------------------------------------------------
    // Helpers
    //------------------------------------------------------------------

    /**
     * @param string $str
     * @param array $array
     * @return string
     */
    private function doSubs(string $str, array $array): string
    {
        if ($str === '') {
            $str = 'QUERY-NOT-FOUND';
        }

        foreach ($array as $name => $value) {
            $str = $this->_subLogic($str, $name, $value);
        }

        return $str;
    }

    private function _subLogic($str, $name, $value): string
    {
        if ($value === false) {
            // true automatically maps to 1, false doesn't,
            // so we'll handle that here
            return str_replace('$' . $name, 0, $str);
        }

        if (($value === null) || ($value === 'null')) {
            // Turn null values (or strings that look like null) in to null SQL statements
            return str_replace('$' . $name, 'null', $str);
        }

        if (is_array($value)) {
            // Attempt to turn array keys into flattened column
            // names. e.g., due.date --> due_date
            foreach ($value as $subname => $subvalue) {
                $derivedName = $name.'_'.$subname;
                $str = $this->_subLogic($str, $derivedName, $subvalue);
            }
            return $str;
        }

        if (is_numeric($value)) {
            return str_replace('$' . $name, $value, $str);
        } else {
            return str_replace('$' . $name, SQLite3::escapeString($value), $str);
        }
    }

    /**
     * @param array $beforeRow
     * @param array $row
     * @return bool
     */
    private function rowChanged(array $beforeRow, array $row): bool
    {
        $keys = array_keys($beforeRow);

        foreach ($keys as $key) {

            if ($beforeRow[$key] === "null") {
                // convert string null to actual null
                $beforeRow[$key] = null;
            }

            if ($key === 'inbox_project') {
                // weird column, just ignore it
                unset($beforeRow[$key]);
            }

            // rename columns if needed

            if ($key === 'task_id') {
                $beforeRow['item_id'] = $row['item_id'];
                unset($beforeRow['task_id']);
            }

            if ($key === 'task_order') {
                $beforeRow['item_order'] = $row['item_order'];
                unset($beforeRow['task_order']);
            }

        }

        // Recalculate keys
        $keys = array_keys($beforeRow);

        $return = false;

        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                // do NOT use the !== operator here
                if ($beforeRow[$key] != $row[$key]) {
                    $return = true;
                }
//            } else {
//                echo "key $key does not exist in upstream table $table\n";
            }
        }

        return $return;
    }
}
