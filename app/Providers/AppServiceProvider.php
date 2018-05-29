<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobFailed;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //任务运行前
        Queue::before(function (JobProcessing $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
            $beforeLog = new Logger('zoroBefore');

            $beforeLogName = storage_path() . '/logs/zoroBefore/' . date('Ymd', time()) . '.log';
            $beforeLog->pushHandler(new StreamHandler($beforeLogName, Logger::INFO));

            (Array) $beforeLogData = $event->job->payload();
            $beforeLogData ['data'] ['command'] = unserialize($beforeLogData ['data'] ['command']);
            $beforeMessage = var_export($beforeLogData, true);

            $beforeLog->addRecord(Logger::INFO,  $beforeMessage . "\r\n\r\n", ['ip' => '127.0.0.1']);
        });

        //任务运行后
        Queue::after(function (JobProcessed $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
            $afterLog = new Logger('zoroAfter');

            $afterLogName = storage_path() . '/logs/zoroAfter/' . date('Ymd', time()) . '.log';
            $afterLog->pushHandler(new StreamHandler($afterLogName, Logger::INFO));

            (Array) $afterLogData = $event->job->payload();
            $afterLogData ['data'] ['command'] = unserialize($afterLogData ['data'] ['command']);
            $afterMessage = var_export($afterLogData, true);

            $afterLog->addRecord(Logger::INFO,  $afterMessage . "\r\n\r\n", ['ip' => '127.0.0.1']);
        });

        /* //任务循环前
        Queue::looping(function () {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });

        //任务失败后
        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
        });

        //异常发生后
        Queue::exceptionOccurred(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
        }); */

        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
