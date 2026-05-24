<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Language;
use App\Entity\User;
use App\Entity\Problem;
use App\Entity\VerdictStatus;
use App\Entity\Submission;
use App\Entity\UserFavorite;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RichFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Languages
        $langs = [
            ['name' => 'PHP', 'cmd' => 'php', 'ext' => 'php'],
            ['name' => 'C++', 'cmd' => 'g++ -std=c++17', 'ext' => 'cpp'],
            ['name' => 'Java', 'cmd' => 'javac', 'ext' => 'java'],
            ['name' => 'Python', 'cmd' => 'python3', 'ext' => 'py'],
        ];

        $languages = [];
        $langRepo = $manager->getRepository(Language::class);
        foreach ($langs as $l) {
            $lang = $langRepo->findOneBy(['name' => $l['name']]);
            if (!$lang) {
                $lang = new Language();
                $lang->setName($l['name']);
                $lang->setCompilerCommand($l['cmd']);
                $lang->setFileExtension($l['ext']);
                $manager->persist($lang);
            }
            $languages[] = $lang;
        }

        // Verdicts
        $verdicts = [];
        $vdefs = [
            ['ok', 'Accepted', 'success'],
            ['wrong_answer', 'Wrong Answer', 'danger'],
            ['runtime_error', 'Runtime Error', 'warning'],
            ['time_limit', 'Time Limit Exceeded', 'info'],
            ['compile_error', 'Compile Error', 'dark'],
            ['pending', 'Pending', 'secondary'],
        ];
        $verRepo = $manager->getRepository(VerdictStatus::class);
        foreach ($vdefs as $vd) {
            $v = $verRepo->findOneBy(['verdict' => $vd[0]]);
            if (!$v) {
                $v = new VerdictStatus();
                $v->setVerdict($vd[0]);
                $v->setDisplayName($vd[1]);
                $v->setColorCode($vd[2]);
                $manager->persist($v);
            }
            $verdicts[] = $v;
        }

        // Users
        $users = [];
        $names = [
            ['admin', 'admin@example.com', true],
            ['alice', 'alice@example.com', false],
            ['bob', 'bob@example.com', false],
            ['carol', 'carol@example.com', false],
        ];
        $userRepo = $manager->getRepository(User::class);
        foreach ($names as $n) {
            $user = $userRepo->findOneBy(['username' => $n[0]]);
            if (!$user) {
                $user = new User();
                $user->setUsername($n[0]);
                $user->setEmail($n[1]);
                $plain = $n[0] === 'admin' ? 'adminpass' : 'password';
                $hashed = $this->hasher->hashPassword($user, $plain);
                $user->setPassword($hashed);
                if ($n[2]) {
                    $user->setIsAdmin(true);
                }
                // small bio and rating
                $user->setBio('Fixture user '.$n[0]);
                $user->setRating(1000 + rand(0, 800));
                $manager->persist($user);
            }
            $users[] = $user;
        }

        // Problems
        $problems = [];
        $categories = ['Algorithms','Math','Strings','Data Structures'];
        $probRepo = $manager->getRepository(Problem::class);
        for ($i = 1; $i <= 12; $i++) {
            $title = "Sample Problem #$i";
            $p = $probRepo->findOneBy(['title' => $title]);
            if (!$p) {
                $p = new Problem();
                $p->setTitle($title);
                $p->setDescription("This is the description for sample problem #$i. Solve it.");
                $difficulties = [400, 700, 900, 1200, 1500];
                $p->setDifficulty($difficulties[array_rand($difficulties)]);
                $p->setCategory($categories[array_rand($categories)]);
                $p->setTimeLimitMs(1000);
                $p->setMemoryLimitMb(256);
                $p->setSuccessCount(rand(0,50));
                $p->setTotalAttempts(rand(0,200));
                $p->setAcceptanceRate($p->getTotalAttempts() ? ($p->getSuccessCount() / max(1, $p->getTotalAttempts())) * 100 : 0);
                $manager->persist($p);
            }
            $problems[] = $p;
        }

        $manager->flush();

        // Test cases via direct DB inserts (entity lacks setters)
        // get DB connection from the EntityManager
        if (!($manager instanceof \Doctrine\ORM\EntityManagerInterface)) {
            throw new \RuntimeException('Expected EntityManagerInterface');
        }
        $em = $manager;
        $conn = $em->getConnection();
        foreach ($problems as $idx => $prob) {
            $pid = $prob->getId();
            // one sample
            $conn->executeStatement('INSERT INTO test_cases (input, expected_output, is_sample, problem_id) VALUES (?, ?, ?, ?)', ["1 2", "3", 1, $pid]);
            // one hidden
            $conn->executeStatement('INSERT INTO test_cases (input, expected_output, is_sample, problem_id) VALUES (?, ?, ?, ?)', ["5 6", "11", 0, $pid]);
        }

        // Submissions
        foreach ($users as $u) {
            // each user makes several submissions
            for ($s = 0; $s < rand(3,6); $s++) {
                $sub = new Submission();
                $sub->setUser($u);
                $prob = $problems[array_rand($problems)];
                $sub->setProblem($prob);
                $sub->setLanguage($languages[array_rand($languages)]);
                $ver = $verdicts[array_rand($verdicts)];
                $sub->setVerdict($ver);
                $sub->setPassedTests(rand(0,5));
                $sub->setTotalTests(5);
                if ($ver->getVerdict() !== 'pending') {
                    $sub->setJudgedAt(new \DateTimeImmutable());
                }
                $manager->persist($sub);
            }
        }

        // Favorites (some simple relations)
        $fav = new UserFavorite();
        $fav->setUser($users[1])->setFavoriteUser($users[2]);
        $manager->persist($fav);

        $fav2 = new UserFavorite();
        $fav2->setUser($users[2])->setFavoriteUser($users[1]);
        $manager->persist($fav2);

        $manager->flush();
    }
}
