<?php
/**
 * Created by PhpStorm.
 * User: idcu
 * Date: 2016/7/1
 * Time: 16:54
 */
namespace Parking\AjaxBundle\Util;
use IDCU\CoreBundle\Hyy\BaseTrait;

trait KeySecretTrait
{
    use BaseTrait;

    protected $appKey;
    protected $appSecret;
    /**
     * @return array
     */
    protected function checkSign($request){
        $timestamp = $request->get('timestamp');
        $appKey = $request->get('appKey');
        $sign = $request->get('sign');

        if($appKey != $this->appKey){
            $res['stat'] = 0;
            $res['msg'] = 'appKey不匹配！';
        }elseif(time() - $timestamp > 120){
            $res['stat'] = 0;
            $res['msg'] = '签名过期！';
        }elseif($sign != $this->generateSign($request)){
            $res['stat'] = 0;
            $res['msg'] = '验证失败！';
        }else{
            $res['stat'] = 1;
            $res['msg'] = '验证成功！';
        }
        !$res['stat'] && $this->end($res);
    }

    /**
     * @return string
     */
    protected function generateSign($request)
    {
        $appSecret = $this->appSecret;
        $params = $request->request->all();
        ksort($params);
        $stringToBeSigned = $appSecret;
        foreach ($params as $k => $v)
        {
            if("@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k,$v);
        $stringToBeSigned .= $appSecret;
        return strtoupper(md5($stringToBeSigned));
    }
}
