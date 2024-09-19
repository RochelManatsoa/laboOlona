<?php

namespace App\Controller\Dashboard;

use App\Repository\Moderateur\MettingRepository;
use App\Service\JitsiMeetService;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConferenceController extends AbstractController
{
    public function __construct(
        private JitsiMeetService $jitsiMeetService,
        private UserService $userService,
        private MettingRepository $mettingRepository,
    ){}

    #[Route('/dashboard/conference', name: 'app_dashboard_conference')]
    public function index(Request $request): Response
    {
        $uuid = $request->query->get('uuid', null);
        $room = 'Conférence Olona Talents';
        $metting = $this->mettingRepository->findOneBy(['customId' => $uuid]);
        if($metting){
            $room = $metting->getTitle();
        }
        $config = $this->jitsiMeetService->generateJitsiConfig($room);

        return $this->render('dashboard/conference/index.html.twig', [
            'metting' => $metting,
            'jitsiConfig' => $config,
            'jitsiDomain' => $this->jitsiMeetService->getDomain(),
        ]);
    }
}
