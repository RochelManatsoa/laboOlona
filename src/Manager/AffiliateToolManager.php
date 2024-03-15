<?php

namespace App\Manager;

use App\Entity\AffiliateTool;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use DateTime;
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
        $tool->setCreeLe(new DateTime());
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
            return $this->findAllAITools();
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
            // ->leftJoin('a.categories', 'c')
            ->leftJoin('a.tags', 't')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function advancedSearchTools(?array $nom = null, ?array $type = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        // if($nom == null && $type == null){
        //     return $this->findAllAITools();
        // }

        // if (!empty($nom)) {
        //     $conditions[] = '(a.nom LIKE :nom )';
        //     $parameters['nom'] = '%' . $nom . '%';
        // }

        // if (!empty($type) ) {
        //     $conditions[] = '(a.type LIKE :type )';
        //     $parameters['type'] = '%' . $type . '%';
        // }

        $qb->select('a')
            ->from('App\Entity\AffiliateTool', 'a')
            // ->leftJoin('j.competences', 'c')
            // ->leftJoin('j.typeContrat', 't')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        // return $qb->getQuery()->getResult();
        return [];
    }
}