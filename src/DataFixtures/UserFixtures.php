<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user
            ->setEmail('nick@test.fr')
            ->setFullName('Nick')
            ->setRoles(['ROLE_USER'])
            ->setPassword('test')
        ;

        $this->addReference(self::getReferenceKey('nick'), $user);

        $admin = new User();
        $admin
            ->setEmail('admin@admin.fr')
            ->setFullName('Admin')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
            ->setPassword('$2y$13$ZNY/SuyjS2i2jd76KPsEHeYdFO.SRcIRr3kpNLaMDkAorIrDdGM6m') // adminadmin
        ;

        $this->addReference(self::getReferenceKey('admin'), $admin);
    }

    public static function getReferenceKey(string $name): string
    {
        return 'fixtures_user_'.$name;
    }
}
