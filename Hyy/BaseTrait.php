<?php
/**
 * Created by PhpStorm.
 * User: idcu
 * Date: 2016/7/1
 * Time: 22:23
 */
namespace IDCU\CoreBundle\Hyy;

trait BaseTrait
{
    /**
     * @param array $res
     */
    protected function end($res){
        echo json_decode($res);exit;
    }
}
