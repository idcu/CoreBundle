<?php
/**
 * Created by PhpStorm.
 * User: idcu
 * Date: 2016/7/1
 * Time: 22:33
 */
namespace IDCU\CoreBundle\Hyy;

trait DoctrineTrait
{
    protected $className;

    protected $alias;

    /**
     * Gets a named object manager.
     * @param string $name The object manager name (null for the default one).
     * @return \Doctrine\ORM\EntityManager
     * @throws \InvalidArgumentException
     */
    protected function getEm($name = null){
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * Gets the repository for a class.
     * @param string $className
     * @return \Doctrine\Orm\EntityRepository
     */
    protected function getEr($className = null){
        $className = $className ? $className : $this->className;
        return $this->getEm()->getRepository($className);
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     * @param string $className
     * @param string $alias
     * @return \Doctrine\Orm\QueryBuilder
     */
    protected function getQb($className = null, $alias = null){
        $className = $className ? $className : $this->className;
        $alias = $alias ? $alias : $this->alias;
        return $this->getEr($className)->createQueryBuilder($alias);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    protected function getPage($request){
        $pageIndex = $request->get('pageIndex') ? intval($request->get('pageIndex')) : 0;
        $pageSize = $request->get('pageSize') ? intval($request->get('pageSize')) : 20;
        $page['offset'] = $pageSize * $pageIndex;
        $page['limit'] = $pageSize;
        return $page;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $page
     * @return array
     */
    protected function pageQb($qb, $page){
        $qr = $qb
            ->setFirstResult($page['offset'])
            ->setMaxResults($page['limit'])
            ->getQuery()
            ->getArrayResult()
        ;
        return $qr;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entity
     * @param $arr
     * @return mixed
     */
    protected function iu($entity, $arr){
        try{
            foreach($arr as $k=>$v){
                $method = 'set'.ucwords($k);
                $entity->$method($v);
            }
            $em = $this->getEm();
            $em->persist($entity);
            $em->flush();
            $res['stat'] = 1;
            $res['msg'] = '更新成功！';
            $res['data'] = $entity;
        }catch(\Exception $e){
            $res['stat'] = 0;
            $res['msg'] = '更新失败！';
            $res['exc'] = $e->getFile().'|'.$e->getMessage().'|'.$e->getTraceAsString();
        }
        return $res;
    }

    /**
     * @param $id
     * @param $className
     * @return mixed
     */
    protected function del($id, $className = null){
        try{
            $className = $className ? $className : $this->className;
            $em = $this->getEm();
            $entity = $em->find($className,(int)$id);
            $em->remove($entity);
            $em->flush();
            $res['stat'] = 1;
            $res['msg'] = '删除成功！';
            $res['id'] = $id;
        }catch(\Exception $e){
            $res['stat'] = 0;
            $res['msg'] = '删除失败！';
            $res['exc'] = $e->getFile().'|'.$e->getMessage().'|'.$e->getTraceAsString();
        }
        return $res;
    }

    /**
     * @param $id
     * @param $className
     * @return mixed
     */
    protected function read($id, $className = null){
        try{
            $className = $className ? $className : $this->className;
            $em = $this->getEm();
            $entity = $em->find($className,(int)$id);
            $res['stat'] = 1;
            $res['msg'] = '查询成功！';
            $res['data'] = $entity;
        }catch(\Exception $e){
            $res['stat'] = 0;
            $res['msg'] = '查询失败！';
            $res['exc'] = $e->getFile().'|'.$e->getMessage().'|'.$e->getTraceAsString();
        }
        return $res;
    }

    /**
     * @param $arr
     * @param $format
     * @param $className
     * @param $alias
     * @return mixed
     */
    protected function sel($arr, $format='array', $className = null, $alias = null){
        try{
            $qb = $this->getQb($className,$alias);
            foreach($arr as $k=>$v){
                switch($k){
                    case 'join':
                        foreach($v as $k1=>$v1){
                            $joinType = $v1['type'] ? $v1['type'].'Join' : 'join';
                            $left = $v1['left'];
                            $right = $v1['right'];
                            $qb = $qb->$joinType($left,$right);
                        }
                        break;
                    case 'where':
                        foreach($v as $k1=>$v1){
                            $whereType = $v1['type'] ? $v1['type'].'Where' : 'where';
                            $operator = $v1['operator']; //操作符
                            $left = $v1['left']; //字段名
                            $right = $v1['right']; //值
                            if(is_array($right)){
                                $qb = $qb->$whereType($qb->expr()->$operator($left,$right[0],$right[1]));
                            }else{
                                $qb = $qb->$whereType($qb->expr()->$operator($left,$right));
                            }
                        }
                        break;
                    case 'groupBy':
                        foreach($v as $k1=>$v1){
                            $groupByType = $v1['type'] ? $v1['type'].'GroupBy' : 'group';
                            $left = $v1['left']; //字段名
                            $qb = $qb->$groupByType($left);
                        }
                        break;
                    case 'orderBy':
                        foreach($v as $k1=>$v1){
                            $orderByType = $v1['type'] ? $v1['type'].'OrderBy' : 'orderBy';
                            $left = $v1['left']; //字段名
                            $right = $v1['right']; //排序
                            $qb = $qb->$orderByType($left,$right);
                        }
                        break;
                    case 'having':
                        foreach($v as $k1=>$v1){
                            $havingType = $v1['type'] ? $v1['type'].'Having' : 'having';
                            $operator = $v1['operator']; //操作符
                            $left = $v1['left']; //字段名
                            $right = $v1['right']; //值
                            if(is_array($left) && is_array($right)){
                                $qb = $qb->$havingType($qb->expr()->$operator($qb->expr()->$left['operator']($left['left']),$right[0],$right[1]));
                            }
                            if(is_array($left)){
                                $qb = $qb->$havingType($qb->expr()->$operator($qb->expr()->$left['operator']($left['left']),$right));
                            }elseif(is_array($right)){
                                $qb = $qb->$havingType($qb->expr()->$operator($left,$right[0],$right[1]));
                            }else{
                                $qb = $qb->$havingType($qb->expr()->$operator($left,$right));
                            }
                        }
                        break;
                    case 'page':
                        isset($v['offset']) && $qb = $qb->setFirstResult($v['offset']);
                        isset($v['limit']) && $qb = $qb->setFirstResult($v['limit']);
                }
            }
            $qr = $qb->getQuery()->getResult($this->getHydrationMode($format));
            $res['stat'] = 1;
            $res['msg'] = '查询成功！';
            $res['data'] = $qr;
        }catch(\Exception $e){
            $res['stat'] = 0;
            $res['msg'] = '查询失败！';
            $res['exc'] = $e->getFile().'|'.$e->getMessage().'|'.$e->getTraceAsString();
        }
        return $res;
    }

    /**
     * @param $format
     * @return int
     */
    protected function getHydrationMode($format = null){
        if(is_null($format)){
            $hydrationMode = 1;
        }elseif(is_int($format)){
            $hydrationMode = $format;
        }else{
            switch($format){
                case 'object':
                    $hydrationMode = 1;
                    break;
                case 'array':
                    $hydrationMode = 2;
                    break;
                case 'scalar':
                    $hydrationMode = 3;
                    break;
                case 'singleScalar':
                    $hydrationMode = 4;
                    break;
                case 'simpleObject':
                    $hydrationMode = 5;
                    break;
                default:
                    $hydrationMode = 1;
            }
        }
        return $hydrationMode;
    }
}
