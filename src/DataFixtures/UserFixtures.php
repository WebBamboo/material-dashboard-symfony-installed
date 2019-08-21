<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
    	$this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setEmail('contact@webbamboo.net');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            '6479pav176587'
        ));
        $manager->persist($user);

        $manager->flush();
    }
}
