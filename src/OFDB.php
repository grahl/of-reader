<?php

namespace Grahl\OFReader;
use CLIFramework\Component\Table\Table;
use CLIFramework\Component\Table\CellAttribute;
use CLIFramework\Component\Table\CompactTableStyle;


class OFDB {

    private $database;

    protected $epoch_offset = 978307200;

    public function __construct() {
        $this->database = new \PDO('sqlite:/home/hendrik/OmniFocusDatabase2');
        $this->database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getDue() {
        $time = strtotime('today 5pm') - $this->epoch_offset;

        $query = $this->database->prepare("SELECT * FROM Task where dateDue <=$time and dateCompleted is Null");
        $tasks = $query->execute();
        $tasks = $query->fetchAll();
        $this->formatTasks($tasks);
    }


    private function formatTasks($tasks) {
        $attrs = new CellAttribute;
        $attrs->setTextOverflow(CellAttribute::ELLIPSIS);
        $table = new Table;
        $table->setHeaders([ 'Name', 'Description' ]);
        $table->setStyle(new CompactTableStyle);
        foreach ($tasks as $task) {
            $table->addRow(array($task['name'], [$attrs, $task['plainTextNote']]));
        }
        echo $table->render();
    }
}



