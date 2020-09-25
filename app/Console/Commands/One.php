<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Log;

class One extends Command {

    protected $name = 'one';//命令名称rrrrrr

    protected $description = 'second_one_command'; // 命令描述，没什么用

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        log::info('test');
        file_put_contents('task.txt' , '0513' . '**' . time() , FILE_APPEND);
        echo 'second_one_command';
        // 功能代码写到这里
    }

}