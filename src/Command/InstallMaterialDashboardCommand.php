<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class InstallMaterialDashboardCommand extends Command
{
    private $passwordEncoder;
    private $entityManager;

    protected static $defaultName = 'app:install-material-dashboard';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder) {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $em;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);
        
        $io->warning('This command is only to be executed on fresh installations!!! If you have an existing database it would be destroyed.');
        
        $continueQuestion = new ConfirmationQuestion('Are you sure you want to continue?(yes/no) ', false);

        if (!$helper->ask($input, $output, $continueQuestion)) {
            return;
        }

        $dbUserQuestion = new Question('Please enter database user: ');
        $dbUserQuestion->setHidden(true);
        $dbUserQuestion->setHiddenFallback(false);

        $dbUser = $helper->ask($input, $output, $dbUserQuestion);
        if(empty($dbUser))
        {
            $io->warning('You must specify database user');
            return;
        }

        $dbPasswordQuestion = new Question('Please enter database password: ');
        $dbPasswordQuestion->setHidden(true);
        $dbPasswordQuestion->setHiddenFallback(false);

        $dbPassword = $helper->ask($input, $output, $dbPasswordQuestion);
        if(empty($dbPassword))
        {
            $io->warning('You must specify database password');
            return;
        }

        $dbNameQuestion = new Question('Please enter database name: ');
        $dbName = $helper->ask($input, $output, $dbNameQuestion);
        if(empty($dbName))
        {
            $io->warning('You must specify database name');
            return;
        }

        $user = false;
        $password = false;
        $addUserQuestion = new ConfirmationQuestion('Do you want to create a master user for the dashboard?(yes/no) ', false);
        if ($helper->ask($input, $output, $addUserQuestion)) {
            $UserQuestion = new Question('Please enter user email: ');
            $UserQuestion->setHidden(true);
            $UserQuestion->setHiddenFallback(false);

            $user = $helper->ask($input, $output, $UserQuestion);
            if(empty($user))
            {
                $io->warning('You must specify user email');
                return;
            }

            $PasswordQuestion = new Question('Please enter user password: ');
            $PasswordQuestion->setHidden(true);
            $PasswordQuestion->setHiddenFallback(false);

            $password = $helper->ask($input, $output, $PasswordQuestion);
            if(empty($password))
            {
                $io->warning('You must specify user password');
                return;
            }
        }

        $this->createEnv($dbName, $dbUser, $dbPassword);
        $this->doctrineDatabaseCreate($output);
        $this->doctrineSchemaUpdate($output);

        if($user && $password)
        {
            $this->createUser($user, $password);
        }

        $this->doctrineSchemaUpdate($output);

        $io->success('The Material Dashboard for Symfony is installed! Now start your project and navigate to /admin');
    }

    private function createUser($user, $password)
    {
        $userObject = new User();
        $userObject->setRoles(['ROLE_SUPER_ADMIN']);
        $userObject->setEmail($user);
        $userObject->setPassword($this->passwordEncoder->encodePassword(
            $userObject,
            $password
        ));
        $this->entityManager->persist($userObject);
        $this->entityManager->flush();
    }

    private function createEnv($dbName, $dbUser, $dbPassword)
    {
        $envContents = file_get_contents(".env");
        $oldDbUrl = "DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name";
        $newDbUrl = sprintf("DATABASE_URL=mysql://%s:%s@127.0.0.1:3306/%s", $dbUser, $dbPassword, $dbName);
        $envLocal = str_replace($oldDbUrl, $newDbUrl, $envContents);
        file_put_contents(".env.local", $envLocal);
    }

    private function doctrineDatabaseCreate(OutputInterface $output)
    {
        $command = $this->getApplication()->find('doctrine:database:create');

        $arguments = [
            'command' => 'doctrine:database:create'
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
    }

    private function doctrineSchemaUpdate(OutputInterface $output)
    {
        $command = $this->getApplication()->find('doctrine:schema:update');

        $arguments = [
            'command' => 'doctrine:schema:update',
            '--force'  => true,
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
    }

    private function assetsInstall(OutputInterface $output)
    {
        $command = $this->getApplication()->find('assets:install');

        $arguments = [
            'command' => 'assets:install'
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
    }
}
