<?php namespace App\Service;

use Dotenv\Dotenv;

/**
 * Primary class for todoist synchronization app
 * It talks to Todoist via the Sync API
 * And stores what it learns in a local sqlite database
 */
class Sync
{
    /** @var Todoist */
    private Todoist $todoist;

    /** @var Data */
    private Data $data;

    /** @var array */
    private array $stats;

    public function init()
    {
        echo "-------------------------------------------\n";
        echo "Script started at " . date('c') . "\n";
        echo "-------------------------------------------\n";

        // Read .env file
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        // Instantiate main Todoist and Database classes
        $this->todoist = new Todoist();
        $this->data = new Data();

        // Initialize stats
        $this->stats = [];
    }

    /**
     * Primary sync method
     */
    public function exec()
    {
        # Pass 1: from todoist -> localdb
        $todoist = $this->todoist->getAll();
        $this->updateLabels($todoist['labels']);
        $this->updateProjects($todoist['projects']);
        $this->updateUser($todoist['user']);
        $this->updateSections($todoist['sections']);
        $this->updateTasks($todoist['items']);    // tasks are still called items in api v8
        $this->updateComments($todoist['notes']); // comments are still called notes in api v8

        # Pass 2: from localdb -> todoist
        $projects = $this->updateProjectsLocally();
        $this->updateSectionsLocally();
        $this->updateTasksLocally($projects);
    }

    /**
     * Finalize execution by reporting stats
     */
    public function term()
    {
        $batch = date('c');
        foreach ($this->stats as $entity => $value) {
            $sum = [
                'batch' => $batch,
                'entity' => $entity,
                'success' => 0,
                'inserted' => 0,
                'updated' => 0,
                'deleted' => 0,
            ];
            /** @var Result $result */
            foreach ($value as $result) {
                echo $entity .
                    "\t" . ($result->getSucceeded() ? 1 : 0) .
                    "\t" . ($result->getInserted() ? 1 : 0) .
                    "\t" . ($result->getUpdated() ? 1 : 0) .
                    "\t" . ($result->getDeleted() ? 1 : 0) .
                    "\t" . $result->getMessage() .
                    "\n";
                if ($result->getSucceeded()) {
                    $sum['success']++;
                }
                if ($result->getInserted()) {
                    $sum['inserted']++;
                }
                if ($result->getUpdated()) {
                    $sum['updated']++;
                }
                if ($result->getDeleted()) {
                    $sum['deleted']++;
                }
            }
            $this->data->insertImportHistory($sum);
        }
        echo "Script finished at " . date('c') . "\n";
    }

    //------------------------------------------------------------------
    // Primary sync methods
    //------------------------------------------------------------------

    /**
     * @param array $labels
     */
    private function updateLabels(array $labels)
    {
        foreach ($labels as $label) {
            $result = $this->data->updateLabel($label);
            $this->stats['labels'][] = $result;
        }
    }

    /**
     * @param array $projects
     */
    private function updateProjects(array $projects)
    {
        foreach ($projects as $project) {
            $result = $this->data->updateProject($project);
            $this->stats['projects'][] = $result;
        }
    }

    /**
     * @param array $user
     */
    private function updateUser(array $user)
    {
        $result = $this->data->updateUser($user);
        $this->stats['user'][] = $result;

        $result = $this->data->insertUserHistory($user);
        $this->stats['user-history'][] = $result;
    }

    /**
     * @param array $sections
     */
    private function updateSections(array $sections)
    {
        foreach ($sections as $section) {
            $result = $this->data->updateSection($section);
            $this->stats['sections'][] = $result;
        }
    }

    /**
     * @param array $tasks
     */
    private function updateTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            // Handle task table
            $result = $this->data->updateTask($task);
            $this->stats['tasks'][] = $result;

            // Break down due-date substructure into upserts
            if (is_array($task['due'])) {
                $taskDue = [
                    'id' => $task['id'],
                    'due_date' => $task['due']['date'],
                    'due_is_recurring' => $task['due']['is_recurring'],
                    'due_string' => $task['due']['string'],
                ];

                $result = $this->data->updateTaskDue($taskDue);
                $this->stats['tasks-due'][] = $result;
            }

            // Lastly, upsert labels
            if (is_array($task['labels']) && count($task['labels']) > 0) {

                // first, fetch what's already stored locally
                $localTaskLabels = $this->data->getTaskLabels($task['id']);
                $a = [];
                foreach ($localTaskLabels as $localTaskLabel) {
                    $a[] = $localTaskLabel['label_id'];
                }

                $upstreamLabelsNotFoundLocally = array_diff($task['labels'], $a);
                $localLabelsNotFoundUpstream = array_diff($a, $task['labels']);
                /*
                    php > $a = [1, 2, 3, 5, 7, 11, 13, 17, 19];
                    php > $b = [1, 3, 5, 7, 9, 11, 13, 15, 17];

                    php > print_r(array_diff($a, $b));
                    Array
                    (
                        [1] => 2
                        [8] => 19
                    )

                    php > print_r(array_diff($b, $a));
                    Array
                    (
                        [4] => 9
                        [7] => 15
                    )
                 */

                //
                foreach ($localLabelsNotFoundUpstream as $label_id) {
                    $taskLabel = [
                        'task_id' => $task['id'],
                        'label_id' => $label_id,
                    ];

                    $result = $this->data->deleteTaskLabel($taskLabel);
                    $this->stats['tasks-labels'][] = $result;
                }

                foreach ($upstreamLabelsNotFoundLocally as $label_id) {
                    $taskLabel = [
                        'task_id' => $task['id'],
                        'label_id' => $label_id,
                    ];

                    $result = $this->data->insertTaskLabel($taskLabel);
                    $this->stats['tasks-labels'][] = $result;
                }
            }
        }
    }

    /**
     * @param array $comments
     */
    private function updateComments(array $comments)
    {
        foreach ($comments as $comment) {
            $result = $this->data->updateComment($comment);
            $this->stats['comments'][] = $result;
        }
    }

    /**
     * @return array
     */
    private function updateProjectsLocally(): array
    {
        $projects = $this->data->getProjects();
        foreach ($projects as $project) {
            if (!$project['is_archived'] && !$project['is_deleted']) {
                $updatedProject = $this->todoist->getProject($project['id']);
                if (array_key_exists('project', $updatedProject)) {
                    $result = $this->data->updateProject($updatedProject['project']);
                    $this->stats['projects-updated'][] = $result;
                } else {
                    // assume it's deleted
                    $project['is_deleted'] = 1;
                    $result = $this->data->updateProject($project);
                    $this->stats['projects-updated'][] = $result;
                }
            }
        }
        return $projects;
    }

    /**
     *
     */
    private function updateSectionsLocally()
    {
        $sections = $this->data->getSections();
        foreach ($sections as $section) {
            if (!$section['is_archived'] && !$section['is_deleted']) {
                $updatedSection = $this->todoist->getSection($section['id']);
                $result = $this->data->updateSection($updatedSection['section']);
                $this->stats['sections-updated'][] = $result;
            }
        }
    }

    /**
     * @param array $projects
     */
    private function updateTasksLocally(array $projects)
    {
        $tasks = $this->data->getTasks();
        foreach ($tasks as $task) {
            if ($this->isTaskActive($task, $projects)) {
                $updatedTask = $this->todoist->getTask($task['id']);
                if (array_key_exists('item', $updatedTask)) {
                    if ($updatedTask['item']['is_deleted']) {
                        $result = $this->data->updateTaskDeleted($updatedTask['item']);
                    } else {
                        $result = $this->data->updateTask($updatedTask['item']);
                        // Look for missing comments
                        $comments = $this->data->getComments($task['id']);
                        foreach ($comments as $comment) {
                            if (!in_array($comment['id'], $this->getCommentIds($updatedTask['notes']))) {
                                $comment['is_deleted'] = 1;
                                $comment['item_id'] = $comment['task_id']; // compatability
                                $this->data->updateComment($comment);
                            }
                        }
                    }
                } else {
                    $result = new Result();
                }
                $this->stats['tasks-updated'][] = $result;
            }
        }
    }

    //------------------------------------------------------------------
    // Additional helpers
    //------------------------------------------------------------------

    /**
     * @param array $task
     * @param array $projects
     * @return bool
     */
    private function isTaskActive(array $task, array $projects): bool
    {
        if ($task['in_history'] || $task['is_deleted'] || $this->taskProjectInactive($task['project_id'], $projects)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param int $projectId
     * @param array $projects
     * @return bool
     */
    private function taskProjectInactive(int $projectId, array $projects): bool
    {
        $inactive = false;

        foreach ($projects as $project) {
            if ($project['id'] === $projectId) {
                if ($project['is_archived'] || $project['is_deleted']) {
                    $inactive = true;
                    break;
                }
            }
        }

        return $inactive;
    }

    /**
     * @param array $comments
     * @return array
     */
    private function getCommentIds(array $comments): array
    {
        $ids = [];

        foreach ($comments as $comment) {
            $ids[] = $comment['id'];
        }

        return $ids;
    }
}
