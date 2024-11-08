<?php

namespace App\Manager;

use App\Entity\AffiliateTool;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AffiliateToolManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private AffiliateToolRepository $affiliateToolRepository,
        private Security $security
    ){        
    }

    public function init(): AffiliateTool
    {
        $tool = new AffiliateTool();
        $tool->setCreeLe(new \DateTime());
        $tool->setDescription("Nouvelle IA ajouté sur Olona Talents");

        return $tool;
    }

    public function save(AffiliateTool $aiTool)
    {
        $this->em->persist($aiTool);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $aiTool = $form->getData();
        $this->save($aiTool);

        return $aiTool;
    }

    public function findAllAITools(): array
    {
        $tools = $this->affiliateToolRepository->findBy(
            ['type' => 'publish'],
            [ 'id' => 'DESC']
        );

        return $tools;
    }

    public function findSearchTools(?string $nom = null, ?string $type = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($nom == null && $type == null){
            return $this->getAllAITools();
        }

        if (!empty($nom)) {
            $conditions[] = '(a.nom LIKE :nom OR t.nom LIKE :nom OR t.nomFr LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        if (!empty($type) ) {
            $conditions[] = '(a.type LIKE :type )';
            $parameters['type'] = '%' . $type . '%';
        }

        $qb->select('a')
            ->from('App\Entity\AffiliateTool', 'a')
            ->leftJoin('a.tags', 't')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }
    
    public function getAllAITools(?int $from = 0, ?int $size = 9, ?string $query = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];
        
        if (!empty($query)) {
            $conditions[] = '(a.nom LIKE :nom OR a.description LIKE :nom OR a.shortDescription LIKE :nom OR a.shortDescription LIKE :nom OR a.descriptionFr LIKE :nom OR a.descriptionEn LIKE :nom)';
            $parameters['nom'] = '%' . $query . '%';
        }

        $qb->select('a')
            ->from('App\Entity\AffiliateTool', 'a');
            if (!empty($query)) {
                $qb
                ->where(implode(' AND ', $conditions))
                ->setParameters($parameters);
            }
            $qb->setMaxResults($size)
            ->setFirstResult($from); 
        
        return $qb->getQuery()->getResult();
    }
}