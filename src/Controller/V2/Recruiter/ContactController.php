<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\Finance\Contrat;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/contract')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
    ){}
    
}
