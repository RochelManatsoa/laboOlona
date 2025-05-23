<?php 

namespace App\Command;

use App\Manager\ModerateurManager;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-project',
    description: 'Init Olona Talents.',
    hidden: false,
    aliases: ['app:init-project']
)]
class InitProjectCommand extends Command
{
    public function __construct(
        private ProfileManager $profileManager,
        private ModerateurManager $moderateurManager,
        private UserService $userService,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Init Olona Talents',
            '==================',
            '',
        ]);

        // the value returned by someMethod() can be an iterator (https://php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        
        $this->moderateurManager->initProject();
        $output->writeln('Languages and sectors initialized');


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->writeln('initializing Olona Talents.');

        return Command::SUCCESS;
    }
}