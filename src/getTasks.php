<?php

namespace Grahl\OFReader;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class getTasks extends Command {
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription('Fetch tasks')
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'Which tasks do you want to see?'
            )
            ->addOption(
                'full',
                null,
                InputOption::VALUE_NONE,
                'If set, the description will be printed'
            );
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $OFDB = new OFDB();
        $type = $input->getArgument('type');
        switch ($type) {
            case 'open':
                $tasks = $OFDB->getOpen();
                break;
            case 'all':
                $tasks = $OFDB->getAll();
                break;
            case 'getDue':
            default:
                $tasks = $OFDB->getDue();
                break;
        }

        $table = new Table($output);
        $formatted_tasks = [];

        if ($input->getOption('full')) {
            $headers = ['Action', 'Note'];
            foreach ($tasks as $task) {
                $formatted_tasks[] =  [$this->wrapText($task[0], 50), $this->wrapText($task[1])];
                $formatted_tasks[] =  new TableSeparator();
            }
            array_pop($formatted_tasks);
        } else {
            $table->setStyle('borderless');
            $headers = array('Action');
            foreach ($tasks as $task) {
                $formatted_tasks[] = [$task[0], $this->formatDate($task[2])];
            }
        }

        $table
            ->setHeaders($headers)
            ->setRows($formatted_tasks);

        $table->render();
    }

    private function wrapText($input, $length = 70) {
        return wordwrap( $input, $length, "\n" , true);
    }

    private function formatDate($timestamp) {

        $today = date('d.m.Y');
        $tomorrow = date('d.m.Y', strtotime('tomorrow'));
        if ($today == date('d.m.Y', $timestamp)) {
          $formatted_date = 'Today';
        } else if ($tomorrow == date('d.m.Y', $timestamp)) {
            $formatted_date = 'Tomorrow';
        } else {
            $formatted_date = date('d.m.Y', $timestamp);
        }

        $formatted_date .= date(' - H:i', $timestamp);
        return $formatted_date;
    }
}