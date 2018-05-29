<?php
namespace APp\Services\payApi\SD;

class PayPost
{

    /**
     * 发送post请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    public function send_post($url, $post_data)
    {

        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60, // 超时时间（单位:s）
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * PHP发送Json对象数据
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return string
     */
    public function http_post_json($url, $param)
    {

        if (empty($url) || empty($param)) {
            return false;
        }
        $param = http_build_query($param);
        try {

            $ch = curl_init(); //初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //正式环境时解开注释
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch); //运行curl
            curl_close($ch);

            if (!$data) {
                throw new \Exception('请求出错');
            }

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
