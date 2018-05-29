<?php
namespace App\Services\rsaSign;
use App\Services\CLogFileHandler;
use App\Services\Log;
use DB;
// 初始化日志
$logHandler= new CLogFileHandler(storage_path()."/pay_logs/".date('Y-m-d').'SignMake.log');
$log = Log::Init($logHandler, 15);
class SignMake{
    protected $dn;
    protected $pass;
    protected $clientPath;//.cer
    protected $zoroCode;
    protected $zoroPath;
    protected $zoroPass;
    protected $dnServer;
    protected $config;
    protected $numberofdays; //有效时长
    public function __construct($dn,$pass,$zoroCode){
        $this->dn = $dn;
        $this->pass = $pass;
        $this->zoroCode = $zoroCode;
        $this->clientPath = storage_path() . '/wl_file/client/'.$zoroCode.'/';
        $this->zoroPath = storage_path() . '/wl_file/server/'.$zoroCode.'/';
        $this->zoroPass = 'zoro_pass_'.$zoroCode.mt_rand(1000,10000);
        if(!is_dir($this->clientPath )){
            mkdir($this->clientPath );
        }
        if(!is_dir($this->zoroPath )){
            mkdir($this->zoroPath );
        }
        $this->dnServer = array(
            "countryName" => 'CN', //所在国家名称
            "stateOrProvinceName" => 'Beijing', //所在省份名称
            "localityName" => 'Beijing', //所在城市名称
            "organizationName" => 'ZoroPay', //注册人姓名
            "organizationalUnitName" => 'AGM', //组织名称
            "commonName" => 'ZoroPay', //公共名称
            "emailAddress" => 'angaomeng@domain.com', //邮箱
        );
        $this->config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 1024, //字节数  512 1024 2048  4096 等
            "private_key_type" => OPENSSL_KEYTYPE_RSA, //加密类型
        );
        $this->numberofdays = 3650;
    }
    public function makeRsa(){
        $privkeypassClient = $this->pass; //私钥密码
        $privkeypassServer = 'AGM'.mt_rand(1000,10000); //私钥密码
        $ClientRsa = self::makeRsaClient($privkeypassClient);
        $ServerRsa = self::makeRsaServer($privkeypassServer);
        if($ClientRsa && $ServerRsa){
            LOG::INFO('Rsa Success '.$this->zoroCode);
            return true;
        }else{
            LOG::ERROR('Rsa Error '.$this->zoroCode);
            return false;
        }
    }
    public function makeRsaClient($privkeypass)
    {
        $dn = $this->dn;
        //$path = storage_path() . '/wl_file';
        $cername = $dn['organizationalUnitName'].$this->zoroCode.'.cer';
        $pfxname = $dn['organizationalUnitName'].$this->zoroCode.'.p12';
        $privkeypass = $privkeypass; //私钥密码
        $numberofdays = $this->numberofdays; //有效时长
        $cerpath = $this->zoroPath.$cername; //生成证书路径
        $pfxpath = $this->clientPath.$pfxname; //密钥文件路径
        $config = $this->config;
        //生成证书
        $privkey = openssl_pkey_new($config);
        $csr = openssl_csr_new($dn, $privkey);
        $sscert = openssl_csr_sign($csr, null, $privkey, $numberofdays);
        openssl_x509_export($sscert, $csrkey); //导出证书$csrkey
        openssl_pkcs12_export($sscert, $privatekey, $privkey, $privkeypass); //导出密钥$privatekey
        if (file_exists($cerpath)) {
            unlink($cerpath);
        }
        if (file_exists($pfxpath)) {
            unlink($pfxpath);
        }
        //生成证书文件
        $fp = fopen($cerpath, "w");
        fwrite($fp, $csrkey);
        fclose($fp);
        //生成密钥文件
        $fp = fopen($pfxpath, "w");
        fwrite($fp, $privatekey);
        fclose($fp);
        //openssl_pkey_export($privkey, $private_key);
        //echo $private_key;
        //echo '<br/>';
        //提取私钥
        //echo openssl_pkey_get_details($privkey)['key'];
        $posPath = '/wl_file/server/'.$this->zoroCode.'/';
        $update = DB::table('zoro_pay_way')->where('pay_way_code',$this->zoroCode)
            ->update(['RsaClient'=>$dn['organizationalUnitName'].$this->zoroCode,'RsaClientPass'=>$privkeypass
            ,'RsaPub'=>$posPath.$cername]);
        if($update !== false){
            //return json_encode(array('status'=>200,'msg'=>'证书生成成功，请妥善保存'));
            LOG::INFO('商户号:'.$this->zoroCode.'生成秘钥文件Client:'.$this->clientPath.',名称:'.$dn['organizationalUnitName'].$this->zoroCode.'.状态:成功');
            return true;
        }else{
            //return json_encode(array('status'=>'R001','msg'=>'证书生成失败，请稍后重试'));
            LOG::INFO('商户号:'.$this->zoroCode.'生成秘钥文件Client:'.$this->clientPath.',名称:'.$dn['organizationalUnitName'].$this->zoroCode.'.状态:失败');
            return false;
        }
    }
    public function makeRsaServer($privkeypass)
    {
        $dn = $this->dnServer;
        $cername = $dn['organizationalUnitName'].$this->zoroCode.'.cer';
        $pfxname = $dn['organizationalUnitName'].$this->zoroCode.'.p12';
        $privkeypass = $privkeypass; //私钥密码
        $numberofdays = $this->numberofdays; //有效时长
        $cerpath = $this->clientPath.$cername; //生成证书路径
        $pfxpath = $this->zoroPath.$pfxname; //密钥文件路径
        $config = $this->config;
        //生成证书
        $privkey = openssl_pkey_new($config);
        $csr = openssl_csr_new($dn, $privkey);
        $sscert = openssl_csr_sign($csr, null, $privkey, $numberofdays);
        openssl_x509_export($sscert, $csrkey); //导出证书$csrkey
        openssl_pkcs12_export($sscert, $privatekey, $privkey, $privkeypass); //导出密钥$privatekey
        if (file_exists($cerpath)) {
            unlink($cerpath);
        }
        if (file_exists($pfxpath)) {
            unlink($pfxpath);
        }
        //生成证书文件
        $fp = fopen($cerpath, "w");
        fwrite($fp, $csrkey);
        fclose($fp);
        //生成密钥文件
        $fp = fopen($pfxpath, "w");
        fwrite($fp, $privatekey);
        fclose($fp);
        //openssl_pkey_export($privkey, $private_key);
        //echo $private_key;
        //echo '<br/>';
        //提取私钥
        //echo openssl_pkey_get_details($privkey)['key'];
        $posPath = '/wl_file/server/'.$this->zoroCode.'/';
        $update = DB::table('zoro_pay_way')->where('pay_way_code',$this->zoroCode)
            ->update(['RsaServer'=>$dn['organizationalUnitName'].$this->zoroCode,'RsaServerPass'=>$privkeypass
                    ,'RsaP12'=>$posPath.$pfxname]);
        if($update !== false){
            //return json_encode(array('status'=>200,'msg'=>'证书生成成功，请妥善保存'));
            LOG::INFO('商户号:'.$this->zoroCode.'生成秘钥文件Server:'.$this->zoroPath.',名称:'.$dn['organizationalUnitName'].$this->zoroCode.'.状态:成功');
            return true;
        }else{
            //return json_encode(array('status'=>'R001','msg'=>'证书生成失败，请稍后重试'));
            LOG::INFO('商户号:'.$this->zoroCode.'生成秘钥文件Server:'.$this->zoroPath.',名称:'.$dn['organizationalUnitName'].$this->zoroCode.'.状态:失败');
            return false;
        }
    }
    
}