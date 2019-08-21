<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class CreateUserCommand extends Command
{
    private $passwordEncoder;
    protected static $defaultName = 'app:create-user';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'User password')
        ;
    }

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder) {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $em;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('user') && $input->getOption('password')) {
            $user = $input->getOption('user');
            $password = $input->getOption('password');

            $userObject = new User();
            $userObject->setRoles(['ROLE_SUPER_ADMIN']);
            $userObject->setEmail($user);
            $userObject->setPassword($this->passwordEncoder->encodePassword(
                $userObject,
                $password
            ));
            $this->entityManager->persist($userObject);
            $this->entityManager->flush();

            $io->success('You have created your user.');
        }
        else
        {
            $io->warning('User and password are mandatory');
        }

    }
}
