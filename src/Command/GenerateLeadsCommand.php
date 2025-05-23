<?php

namespace App\Command;

use App\Entity\Coworking\Event;
use App\Entity\Coworking\Reservation;
use App\Entity\User;
use App\Entity\Marketing\Lead;
use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\ContactForm;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Finance\ContratRepository;
use App\Repository\Marketing\SourceRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-leads',
    description: 'Add pageUrl to log',
    hidden: false,
    aliases: ['app:generate-leads']
)]
class GenerateLeadsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContratRepository $contratRepository,
        private SourceRepository $sourceRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'The source.')
            ->setDescription('Génération des leads pour les candidats.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = $input->getArgument('source');
        $source = $this->sourceRepository->findOneBy(['slug' => $slug]);
        $repository = $this->entityManager->getRepository($source->getNameSpace());
        $io->writeln('Génération des leads pour '. $source->getName());

        if($slug == 'annonce-entreprise'){ 
            $elements = $repository->findAll();
            $sourceEntreprise = $this->sourceRepository->findOneBy(['slug' => 'entreprise']);
            foreach ($elements as $element) {
                if($element instanceof EntrepriseProfile && $element->getStatus() !== EntrepriseProfile::STATUS_BANNED && $element->getStatus() !== EntrepriseProfile::STATUS_PENDING ){
                    $user = $element->getEntreprise();
                    if($user instanceof User){
                        $lead = new Lead();
                        if(count($element->getJobListings()) > 0){
                            $lead->setSource($source);
                            $lead->setComment($source->getDescription());
                            $io->writeln(sprintf('Lead annonce entreprise créé : %d', $element->getId()));
                        }else{
                            $lead->setSource($sourceEntreprise);
                            $lead->setComment($sourceEntreprise->getDescription());
                            $io->writeln(sprintf('Lead entreprise créé : %d', $element->getId()));
                        }
                        $lead->setFullName($user);
                        $lead->setEmail($user->getEmail());
                        $lead->setPhone($user->getTelephone());
                        $lead->setUser($user);
                        $this->entityManager->persist($lead);
                    }
                }
            }
        }

        if($slug == 'coworking'){ 
            $elements = $repository->findAll();
            $emails = [];  

            foreach ($elements as $element) {
                $user = $element->getUser();
                if ($user instanceof User) {
                    $email = $user->getEmail(); 

                    if (!isset($emails[$email])) {
                        $emails[$email] = [
                            'count' => 0,
                            'user' => $user
                        ];
                    }
                    $emails[$email]['count']++;  
                }
            }

            foreach ($emails as $email => $data) {
                $user = $data['user'];
                $count = $data['count'];

                $lead = new Lead();
                $lead->setSource($source);
                $lead->setComment($source->getDescription() . ' - ' . $count . ' évènements');
                $lead->setFullName($user->getFullName()); 
                $lead->setEmail($email);
                $lead->setPhone($user->getTelephone());  
                $lead->setUser($user);
                $this->entityManager->persist($lead);
                $this->entityManager->flush();  

                $io->writeln(sprintf('Lead créé : %s', $source->getName()));
            }

        }

        if($slug == 'formulaire-de-contact-site-coworking' || $slug == 'formulaire-de-contact-site-olona-talents'){ 
            $elements = $repository->findLatestReservationByUniqueEmail();
            foreach ($elements as $element) {
                $lead = new Lead();
                $lead->setSource($source);
                $lead->setEmail($element->getEmail());
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $element->getEmail()]);
                if($user){
                    $lead->setUser($user);
                }
                if($element instanceof Reservation){
                    $lead->setComment($source->getDescription() . ' - ' . $element->getDescription());
                    $lead->setFullName($element->getFullName());
                    $lead->setPhone($element->getPhone());
                    $this->entityManager->persist($lead);
                    $io->writeln(sprintf('Lead créé : %s', $source->getName()));
                }
                if($element instanceof ContactForm){
                    $lead->setComment($source->getDescription() . ' - ' . $element->getMessage());
                    $lead->setFullName($element->getTitre());
                    $lead->setPhone($element->getNumero());
                    $this->entityManager->persist($lead);
                    $io->writeln(sprintf('Lead créé : %s', $source->getName()));
                }
            }

        }

        $this->entityManager->flush();

        $io->success('Les leads ont été ajoutés avec succès.');

        return Command::SUCCESS;
    }
}