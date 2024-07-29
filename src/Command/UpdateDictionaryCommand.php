<?php 

namespace App\Command;

use App\Entity\Word;
use App\Entity\Cron\CronLog;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-dictionary',
    description: 'Update dictionary Olona Talents.',
    hidden: false,
    aliases: ['app:update-dictionary']
)]
class UpdateDictionaryCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private WordRepository $wordRepository,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Update dictionary Olona Talents.',
            '==================',
            '',
        ]);

        $startTime = new \DateTime();

        $emailsSent = 0;
        $update = 0;
        $new = 0;
        
        $profiles = $this->candidateProfileRepository->findProfilesForDictionary();
        $output->writeln(count($profiles) .' profiles à vérifier');
        foreach ($profiles as $profile) {
            if ($profile->getBadKeywords() != null) {
                $words = explode(', ', $profile->getBadKeywords());
                foreach ($words as $key => $word) {
                    $wordEntity = $this->wordRepository->findOneBy(['content' => strtolower($word)]);
                    if ($wordEntity) {
                        $currentCount = $wordEntity->getUsageCount();
                        $wordEntity->setUsageCount($currentCount + 1);
                        $output->writeln($update + 1 .' mis à jour ');
                        $update += 1;
                        
                        $this->em->persist($wordEntity);
                    }else{
                        $wordEntity = (new Word())->setContent(strtolower($word))->setUsageCount(1);
                        $output->writeln($new + 1 .' nouveaux ');
                        $new += 1;
                    }
                    $this->em->persist($wordEntity);
                    $this->em->flush();
                }
            }
        }

        $endTime = new \DateTime();

        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:update-dictionary')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();
        
        $output->writeln('Updated!');

        $output->writeln('Whoa!');

        $output->write('You are about to ');
        $output->writeln('update dictionary Olona Talents.');

        return Command::SUCCESS;
    }
}