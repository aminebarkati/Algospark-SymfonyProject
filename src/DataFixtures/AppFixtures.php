<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Language;
use App\Entity\VerdictStatus;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * AppFixtures seeds languages, verdicts and an admin user.
 * Passwords are hashed via the injected password hasher.
 */

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $langs = [
            ['C++','g++ -O2 -std=c++17','.cpp'],
            ['Python','python3','.py'],
            ['Java','javac -encoding UTF-8','.java'],
            ['JavaScript','node','.js'],
            ['C','gcc -O2','.c']
        ];

        foreach ($langs as [$name,$cmd,$ext]) {
            $l = new Language();
            $l->setName($name);
            $l->setCompilerCommand($cmd);
            $l->setFileExtension($ext);
            $manager->persist($l);
        }

        $verdicts = [
            ['AC','Accepted','#28a745'],
            ['WA','Wrong Answer','#dc3545'],
            ['TLE','Time Limit Exceeded','#fd7e14'],
            ['MLE','Memory Limit Exceeded','#6f42c1'],
            ['RE','Runtime Error','#e83e8c'],
            ['CE','Compilation Error','#17a2b8'],
            ['PE','Presentation Error','#ffc107'],
            ['PENDING','Pending','#6c757d'],
        ];

        foreach ($verdicts as [$v,$display,$color]) {
            $vs = new VerdictStatus();
            $vs->setVerdict($v);
            $vs->setDisplayName($display);
            $vs->setColorCode($color);
            $manager->persist($vs);
        }

        // Create a default admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        $manager->flush();
    }
}
