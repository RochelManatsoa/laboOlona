<?php

namespace App\WhiteLabel\Command\Client1;

use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use App\WhiteLabel\Entity\Client1\Availability;
use Symfony\Component\Console\Attribute\AsCommand;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\WhiteLabel\Entity\Client1\Candidate\TarifCandidat;

#[AsCommand(
    name: 'app:client1:index-candidate-profiles',
    description: 'Index all candidate profiles to Elasticsearch',
    hidden: false,
    aliases: ['app:client1:index-candidate-profiles']
)]
class IndexCandidateProfilesCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager, 
        private ElasticsearchService $elasticsearch,
    )
    {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index all candidate profiles to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profiles = $this->entityManager->getRepository(CandidateProfile::class)->findStatusValid();

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
                'availability'      => $this->getAvailabilityStr($profile),
                'tarifCandidat'     => $this->getDefaultTarifCandidat($profile),
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
    
    public function getDefaultTarifCandidat(CandidateProfile $profile): string
    {
        $tarif = '';
        $tarifCandidat = $profile->getTarifCandidat();
        if($tarifCandidat instanceof TarifCandidat){
            
        }

        return $tarif;
    }

    private function getAvailabilityStr(CandidateProfile $candidateProfile): string
    {
        $status = 'Disponible';
        $availability = $candidateProfile->getAvailability();
        if($availability instanceof Availability){
            switch ($availability->getNom()) {
                case 'immediate':
                    $status = 'Disponible';
                    break;

                case 'from-date':
                    $status = 'A partir du '. $availability->getDateFin()->format('d/m/Y');
                    break;

                case 'full-time':
                    $status = 'Temps plein';
                    break;

                case 'part-time':
                    $status = 'Temps partiel';
                    break;

                case 'not-available':
                    $status = 'Non disponible';
                    break;
                
                default:
                    $status = 'Non renseigné';
                    break;
            }
        }

        return $status;
    }
}
