<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager):void
    {
        $user = new User();
        $user->setEmail('jardin.harmonie@gmail.com');            // pseudo de connexion
        $user->setRoles(['ROLE_ADMIN']);         // rôle admin
        $user->setPassword($this->passwordHasher->hashPassword($user, 'sae301')); // mot de passe hashé

        $manager->persist($user);
        $manager->flush();
    }
}
