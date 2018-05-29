<?php

namespace App\Jobs\notify;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;

class ZoroPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 请在自己本地开启redis-server
     * 运行命令：php artisan queue:work --daemon --quiet --queue=Zoro --tries=7 --timeout=60 --sleep=3
     * --daemon 优化CPU实用率
     * --quiet 关闭在命令窗口中显示 可记录文件
     * --queue 优先名称队列
     * --tries 重试次数
     * --timeout 每次超时时间
     * --sleep 每次任务执行完毕后休息多长时间
     */

    protected $toUrl;
    protected $jsonData;
    protected $bankOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($toUrl, $jsonData, $bankOrder)
    {
        $this->toUrl = $toUrl; // 异步通知的地址
        $this->jsonData = $jsonData; // 异步内容
        $this->bankOrder = $bankOrder; // 所需要更改的订单号条件
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $CURL = curl_init();

        curl_setopt($CURL, CURLOPT_URL, $this->toUrl);
        curl_setopt($CURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($CURL, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($CURL, CURLOPT_POST, true);
        curl_setopt($CURL, CURLOPT_HEADER, false);
        curl_setopt($CURL, CURLOPT_POSTFIELDS, $this->jsonData);
        curl_setopt($CURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($CURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        $Response = curl_exec($CURL);

        curl_close($CURL);

        if (strip_tags(str_replace(PHP_EOL, '', $Response)) != 'result=SUCCESS') {
            switch ($this->attempts()) {
                case 1:
                    // 2 分钟重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 2 * 60 . "\r\n";
                    $this->release(2 * 60);
                    break;
                case 2:
                    // 5 分钟重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 5 * 60 . "\r\n";
                    $this->release(5 * 60);
                    break;
                case 3:
                    // 10 分钟重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 10 * 60 . "\r\n";
                    $this->release(10 * 60);
                    break;
                case 4:
                    // 1 小时重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 60 * 60 . "\r\n";
                    $this->release(60 * 60);
                    break;
                case 5:
                    // 2 小时重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 120 * 60 . "\r\n";
                    $this->release(120 * 60);
                    break;
                case 6:
                    // 6 小时重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 360 * 60 . "\r\n";
                    $this->release(360 * 60);
                    break;
                case 7:
                    // 15 小时重试
                    DB::table('zoro_trade_payment_record')
                        ->where('merchant_order_no', '=', $this->bankOrder)
                        ->increment('notify_counts');

                    echo 'ERROR ' . 900 * 60 . "\r\n";
                    $this->release(900 * 60);
                    break;
                default:
                    echo "Interrupted Error";
                    break;
            }
        } else {
            DB::table('zoro_trade_payment_record')
                ->where('merchant_order_no', '=', $this->bankOrder)
                ->update(['notify_result' => 1]);

            echo 'SUCCESS';
        }
    }
}
