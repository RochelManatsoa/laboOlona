<?php

namespace App\WhiteLabel\Command\Client1;

use App\Twig\ProfileExtension;
use App\Entity\CandidateProfile;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:client1:index-candidate-profiles',
    description: 'Index all candidate profiles to Elasticsearch',
    hidden: false,
    aliases: ['app:client1:index-candidate-profiles']
)]
class IndexCandidateProfilesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em, 
        private ElasticsearchService $elasticsearch,
        private ProfileExtension $extension
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index all candidate profiles to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profiles = $this->em->getRepository(CandidateProfile::class)->findStatusValid();

        foreach ($profiles as $profile) {
            $body = [
                'titre'             => $profile->getTitre(),
                'resume'            => $profile->getResume(),
                'localisation'      => $profile->getLocalisation(),
                'technologies'      => $profile->getTechnologies(),
                'fileName'          => $profile->getFileName(),
                'tools'             => $profile->getTools(),
                'badKeywords'       => $profile->getBadKeywords(),
                'resultFree'        => $profile->getResultFree(),
                'metaDescription'   => $profile->getMetaDescription(),
                'traductionEn'      => $profile->getTraductionEn(),
                'availability'      => $this->extension->getAvailabilityStr($profile),
                'tarifCandidat'     => $this->extension->getDefaultTarifCandidat($profile),
                'competences'   => [],
                'experiences'   => [],
                'secteurs'      => [],
                'langages'      => [],
            ];

            foreach ($profile->getCompetences() as $competence) {
                $body['competences'][] = [
                    'nom' => $competence->getNom(),
                ];
            }

            $experienceYears = 0;

            foreach ($profile->getExperiences() as $experience) {
                $dateDebut = $experience->getDateDebut();
                $dateFin = $experience->getDateFin() ?? new \DateTime(); // en cours

                if ($dateDebut instanceof \DateTimeInterface && $dateFin instanceof \DateTimeInterface) {
                    $interval = $dateDebut->diff($dateFin);
                    $experienceYears += $interval->y; // Ajoute le nombre d'années complètes
                }

                $body['experiences'][] = [
                    'nom'        => $experience->getNom(),
                    'description'=> $experience->getDescription(),
                ];
            }
            
            $body['gender'] = $profile->getGender(); // MALE, FEMALE, OTHER
            $body['province'] = $profile->getProvince(); // ex: Antananarivo
            $body['experience_years'] = $experienceYears;

            foreach ($profile->getSecteurs() as $secteur) {
                $body['secteurs'][] = [
                    'nom' => $secteur->getNom(),
                ];
            }

            foreach ($profile->getLangages() as $langage) {
                $body['langages'][] = [
                    'nom' => $langage->getLangue()->getNom(),
                    'code' => $langage->getLangue()->getCode(),
                ];
            }


            $this->elasticsearch->index([
                'index' => 'candidate_white_label_index',
                'id'    => $profile->getId(),
                'body'  => $body,
            ]);

            $this->elasticsearch->index([
                'index' => 'candidate_white_label_index',
                'id'    => $profile->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Candidate Profile ID: ' . $profile->getId());
        }

        return Command::SUCCESS;
    }
}
