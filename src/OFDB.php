<?php

namespace Grahl\OFReader;

use Symfony\Component\Yaml\Yaml;

class OFDB
{

    private $database;

    protected $epoch_offset = 978307200;

    public function __construct()
    {

        $file = __DIR__.'/../config.yml';
        $config = Yaml::parse(file_get_contents($file));

        if (isset($config['application']) && isset($config['application']['database'])) {
            $db_file = $config['application']['database'];
        } else {
            $db_file = $_SERVER['HOME'].'/OmniFocusDatabase2';
        }

        $this->database = new \PDO('sqlite:'.$db_file);
        $this->database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getDue()
    {
        $time = strtotime('today 5pm') - $this->epoch_offset;

        $query = $this->database->prepare(
            "SELECT * FROM Task where dateDue <=:time and dateDue > 0 AND dateCompleted is Null and projectInfo is Null"
        );
        $query->execute([':time' => $time]);
        $tasks = $query->fetchAll();

        return $this->formatTasks($tasks);
    }

    public function getAll()
    {
        $query = $this->database->prepare("SELECT * FROM Task where projectInfo is Null");
        $query->execute();
        $tasks = $query->fetchAll();

        return $this->formatTasks($tasks);

    }

    public function getAvailable()
    {
        $now = time() - -$this->epoch_offset;
        $query = $this->database->prepare("SELECT * FROM Task where projectInfo is Null AND dateCompleted IS Null AND (dateToStart>=:start OR dateToStart is NULL)");
        $query->execute([':start' => $now]);
        $tasks = $query->fetchAll();

        return $this->formatTasks($tasks);

    }

    public function getOpen()
    {
        $query = $this->database->prepare("SELECT * FROM Task where dateCompleted is Null and projectInfo is Null");
        $query->execute();
        $tasks = $query->fetchAll();

        return $this->formatTasks($tasks);

    }


    private function convertDate($input)
    {
        if ($input > 0) {
            return $this->epoch_offset + $input;
        } else {
            return 0;
        }
    }

    private function fetchParent($id)
    {
        $query = $this->database->prepare("SELECT * FROM task WHERE persistentIdentifier=:id");
        $query->execute([':id' => $id]);

        return $query->fetch();
    }

    private function formatTasks($tasks)
    {

        if (!$tasks) {
            return array();
        }

        foreach ($tasks as $task) {
            $parentTask = $this->fetchParent($task['parent']);
            $project_label = '';
            $separator = '';
            while (empty($parentTask['projectInfo'])) {
                $project_label = $parentTask['name'].$separator.$project_label;
                $parentTask = $this->fetchParent($parentTask['parent']);
                $separator = ': ';
            }
            $project_label = $parentTask['name'].$separator.$project_label;
            $formatted_task = new \stdClass();
            $formatted_task->project = $project_label;
            $formatted_task->name = $task['name'];
            $formatted_task->plainTextNote = $task['plainTextNote'];
            $formatted_task->dateDue = $this->convertDate($task['dateDue']);
            $formatted_task->dateToStart = $this->convertDate($task['dateToStart']);
            $formatted_tasks[] = $formatted_task;

        }

        return $formatted_tasks;
    }
}



