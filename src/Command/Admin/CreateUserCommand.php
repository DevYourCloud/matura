<?php

namespace App\Command\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * Inspired by the EasyAdminBundle Demo site : https://github.com/EasyCorp/easyadmin-demo
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[AsCommand(
    name: 'app:add-user',
    description: 'Creates users and stores them in the database'
)]
class CreateUserCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $users
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addArgument('username', InputArgument::OPTIONAL, 'The full name of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('password') && null !== $input->getArgument('email') && null !== $input->getArgument('username')) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the full name if it's not defined
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Full Name</info>: '.$username);
        } else {
            $username = $this->io->ask('Full Name', null, [$this, 'validateUsername']);
            $input->setArgument('username', $username);
        }

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, [$this, 'validateEmail']);
            $input->setArgument('email', $email);
        }

        // Ask for the password if it's not defined
        $password = $input->getArgument('password');
        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden(
                'Password - min 6 chars (your type will be hidden)',
                [$this, 'validatePassword']
            );
            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plainPassword = $input->getArgument('password');
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $isAdmin = $input->getOption('admin');

        // make sure to validate the user data is correct
        $this->validateUserData($email);

        // create the user and hash its password
        $user = new User();
        $user->setFullName($username);
        $user->setEmail($email);
        $user->setRoles([$isAdmin ? 'ROLE_ADMIN' : 'ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s', $isAdmin ? 'Administrator user' : 'User', $user->getUserIdentifier()));

        return Command::SUCCESS;
    }

    private function validatePassword(?string $plainPassword): string
    {
        if (empty($plainPassword)) {
            throw new \InvalidArgumentException('The password can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 6) {
            throw new \InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $plainPassword;
    }

    private function validateEmail(?string $email): string
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new \InvalidArgumentException('The email should look like a real email.');
        }

        return $email;
    }

    private function validateUsername(?string $username): string
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('The username can not be empty.');
        }

        return $username;
    }

    private function validateUserData($email): void
    {
        // check if a user with the same email already exists.
        $existingEmail = $this->users->findOneBy(['email' => $email]);

        if (null !== $existingEmail) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates new users and saves them in the database:
              <info>php %command.full_name%</info> <comment>username password email</comment>
            By default the command creates regular users. To create administrator users,
            add the <comment>--admin</comment> option:
              <info>php %command.full_name%</info> username password email <comment>--admin</comment>
            If you omit any of the three required arguments, the command will ask you to
            provide the missing values:
              # command will ask you for the email
              <info>php %command.full_name%</info> <comment>username password</comment>
              # command will ask you for the email and password
              <info>php %command.full_name%</info> <comment>username</comment>
              # command will ask you for all arguments
              <info>php %command.full_name%</info>
            HELP;
    }
}
