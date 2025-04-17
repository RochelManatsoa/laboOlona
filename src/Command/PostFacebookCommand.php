<?php

namespace App\Command;

use App\Entity\Entreprise\JobListing;
use App\Service\FacebookPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:post-facebook',
    description: 'Publie un test sur la page Facebook configurée',
    hidden: false,
    aliases: ['app:post-facebook']
)]
class PostFacebookCommand extends Command
{
    public function __construct(
        private FacebookPublisher $facebookPublisher,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private SluggerInterface $sluggerInterface
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Publie une annonce sur la page Facebook Olona Talents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $annonces = $this->entityManager->getRepository(JobListing::class)->findJoblistingsForPostFacebook();
        if(is_array($annonces) && count($annonces) > 0){
            $annonce = $annonces[0];
            $io->section('Publication annonces premium sur la page facebook Olona Talents...');
            $postText = sprintf(
                "🚀 Saisissez votre chance : Devenez %s chez %s !\n\n🔥%s\n\n📍 Lieu : %s\n💼 Contrat : %s\n\nPostulez dès maintenant 👉 %s \n\n\n #Recrutement #Opportunité #Carrière",
                $annonce->getTitre(),
                $annonce->getEntreprise()->getNom(),
                $annonce->getShortDescription(),
                $annonce->getLieu(),
                $annonce->getTypeContrat(),
                $this->urlGenerator->generate('app_olona_talents_view_job_offer', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            );
    
            $success = false;
            // $success = $this->facebookPublisher->publish($postText);
    
            if ($success) {
                $annonce->setIsPublishedOnFacebook(true);
                $this->entityManager->persist($annonce);
                $this->entityManager->flush();
                $output->writeln('<info>✅ Publication réussie !</info>');
                return Command::SUCCESS;
            }
        }


        $output->writeln('<error>❌ La publication a échoué.</error>');
        
        $io->success('Commande terminée.');
        return Command::FAILURE;
    }
}