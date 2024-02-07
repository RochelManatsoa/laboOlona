<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\ReferrerProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/moderateur/coopteur')]
class CoopteurController extends AbstractController
{
    #[Route('/{customId}', name: 'app_dashboard_moderateur_coopteur_view')]
    public function view(Request $request, ReferrerProfile $referrerProfile): Response
    {
        return $this->render('dashboard/moderateur/coopteur/view.html.twig', [
            'coopteur' => $referrerProfile,
        ]);
    }
}
