<?php

namespace BaseBundle\Repository;

/**
 * PromotionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PromotionRepository extends \Doctrine\ORM\EntityRepository
{

    public function findCodePromo($id)
    {
        $q=$this->getEntityManager()->createQuery('select m from BaseBundle:Promotion m where  m.code =:code ')
            ->setParameter('code',$id);
        ;
        return $q->getResult();
    }
}