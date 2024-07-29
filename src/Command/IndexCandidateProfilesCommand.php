<?php
namespace App\Command;

use App\Twig\ProfileExtension;
use App\Entity\CandidateProfile;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:index-candidate-profiles',
    description: 'Index all candidate profiles to Elasticsearch',
    hidden: false,
    aliases: ['app:index-candidate-profiles']
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
        $premiums = $this->em->getRepository(CandidateProfile::class)->findStatusPremium();

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

            foreach ($profile->getExperiences() as $experience) {
                $body['experiences'][] = [
                    'nom'       => $experience->getNom(),
                    'description' => $experience->getDescription(),
                ];
            }

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
                'index' => 'candidate_profile_index',
                'id'    => $profile->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Candidate Profile ID: ' . $profile->getId());
        }

        foreach ($premiums as $profile) {
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

            foreach ($profile->getExperiences() as $experience) {
                $body['experiences'][] = [
                    'nom'       => $experience->getNom(),
                    'description' => $experience->getDescription(),
                ];
            }

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
                'index' => 'candidate_premium_index',
                'id'    => $profile->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Premium Candidate Profile ID: ' . $profile->getId());
        }

        return Command::SUCCESS;
    }
}
