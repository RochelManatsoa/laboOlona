<?php 

namespace App\WhiteLabel\Command\Client1;

use Symfony\Component\Uid\Uuid;
use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\WhiteLabel\Entity\Client1\Secteur;
use Symfony\Component\Console\Command\Command;
use App\WhiteLabel\Entity\Client1\ReferrerProfile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init-white-label-project',
    description: 'Init BOA Talents.',
    hidden: false,
    aliases: ['app:init-white-label-project']
)]
class InitProjectCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ){
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Init BOA Talents',
            '==================',
            '',
        ]);

        // the value returned by someMethod() can be an iterator (https://php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
           
        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $referrer = new ReferrerProfile();
            $referrer->setCreatedAt(new \DateTime());
            $referrer->setReferrer($user);
            $referrer->setStatus(ReferrerProfile::STATUS_PENDING);
            $referrer->setCustomId(new Uuid(Uuid::v1()));
            $this->entityManager->persist($referrer);
            $this->entityManager->persist($user);
        }
        $secteurs = [
            ['name' => 'Analyse et risque', 'slug' => 'analyse-et-risque'],
            ['name' => 'Animation commerciale', 'slug' => 'animation-commerciale'],
            ['name' => 'Audit', 'slug' => 'audit'],
            ['name' => 'Commerciale : Réseau et centre d\'affaires', 'slug' => 'commerciale-reseau-et-centre-d-affaires'],
            ['name' => 'Compliance', 'slug' => 'compliance'],
            ['name' => 'Contrôle', 'slug' => 'controle'],
            ['name' => 'Digitale', 'slug' => 'digitale'],
            ['name' => 'Finance et comptabilité', 'slug' => 'finance-et-comptabilite'],
            ['name' => 'Gestion de crédit', 'slug' => 'gestion-de-credit'],
            ['name' => 'Informatique', 'slug' => 'informatique'],
            ['name' => 'Juridique', 'slug' => 'juridique'],
            ['name' => 'Marketing et communication', 'slug' => 'marketing-et-communication'],
            ['name' => 'Monétique et transfert d\'argent', 'slug' => 'monetique-et-transfert-d-argent'],
            ['name' => 'Moyen généraux', 'slug' => 'moyen-generaux'],
            ['name' => 'Opérations (Locale et étrangère)', 'slug' => 'operations-locale-et-etrange'],
            ['name' => 'Organisation et qualité', 'slug' => 'organisation-et-qualite'],
            ['name' => 'Projet', 'slug' => 'projet'],
            ['name' => 'Recouvrement et contentieux', 'slug' => 'recouvrement-et-contentieux'],
            ['name' => 'Ressources humaines', 'slug' => 'ressources-humaines'],
            ['name' => 'Sécurité', 'slug' => 'securite'],
            ['name' => 'Trésorerie', 'slug' => 'tresorerie'],
        ];     


        // foreach ($secteurs as $value) {
        //     $secteur = new Secteur();
        //     $secteur
        //         ->setNom($value['name'])
        //         ->setSlug($value['slug'])
        //     ;
        //     $this->entityManager->persist($secteur);
        // }

        // $user = new User();
        // $user->setEmail('admin@gmail.com');
        // $user->setNom('Admin');
        // $user->setPrenom('BOA Talents');
        // $user->setTelephone('0340268554');
        // $user->setAdress('12 rue de la gare');
        // $user->setCity('Paris');
        // $user->setPostalCode('75001');
        // $user->setType(User::ACCOUNT_MODERATEUR);
        // $user->setRoles(['ROLE_ADMIN']);
        // $user->setIsVerified(true);
        // $user->setDateInscription(new \DateTime());
        // $hashedPassword = $this->passwordHasher->hashPassword($user, '000000');
        // $user->setPassword($hashedPassword);
        // $this->entityManager->persist($user);

        $this->entityManager->flush();
        $output->writeln('Sectors initialized');

        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->writeln('initializing BOA Talents.');

        return Command::SUCCESS;
    }
}