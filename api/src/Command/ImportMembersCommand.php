<?php

namespace App\Command;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Entity\Person\Nationality;
use App\Entity\Person\Person;
use App\Entity\User\User;
use App\Repository\Person\PersonRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportMembersCommand extends Command
{
    protected static $defaultName = 'app:import-members';

    private $container;

    public function __construct(string $name = null, ContainerInterface $container)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('file', InputArgument::REQUIRED, 'Argument description')
            ->addArgument('org', InputArgument::REQUIRED, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('file');
        $arg2 = $input->getArgument('org');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $manager = $this->container->get('doctrine.orm.default_entity_manager');
        $projDir = $this->container->get('kernel')->getProjectDir();
        $fileName = $projDir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR.'member'.DIRECTORY_SEPARATOR.$arg1;
        $io->note($fileName);
//        $inputFileName = __DIR__ . '/sampleData/example1.xls';
        $spreadsheet = IOFactory::load($fileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
//        var_dump($sheetData);
        /** @var PersonRepository $personRepo */
        $personRepo = $this->container->get('doctrine')->getRepository(Person::class);
        $orgRepo = $this->container->get('doctrine')->getRepository(Organisation::class);
        /** @var Organisation $org */
        $org = $orgRepo->find((int)$arg2);
        if (empty($org)) {
            throw new \Exception('Org not found');
        }
        $io->note('Importing members for '.$org->getName());

        $countries = [
            'SING' => 'Singapore',
            'MAL' => 'Malaysia',
            'CHI' => 'China',
            'PHI' => 'Philippines',
            'INS' => 'Indonesia',
            'TWN' => 'Taiwan',
            'VIE' => 'Vietnam',
            'THA' => 'Thailand',
        ];

        foreach ($sheetData as $line => $row) {
            if ($line === 1) {
                continue;
            }
            $nric = strtoupper(trim($row['C']));
            if (empty($nric)) {
                continue;
            }
            $person = $personRepo->findOneByNricNumber($nric);
            if (empty($person)) {
                $person = new Person();
                $email = $nric.'.nric.auto-gen@whatwechat.net';
                $person->setEmail($email);
                $person->setPhoneNumber($nric);
                $user = new User();
                $user->setPlainPassword($nric);
                $user->setEmail($email);
                $user->setIdNumber($nric);
                $user->setPhone($nric);
                $person->setUser($user);
                $user->setPerson($person);

            }
            foreach ($row as $col => $cell) {
                $user = $person->getUser();
                switch ($col) {
                    case 'A':
                        $person->setGivenName($cell);
                        break;
                    case 'B':
                        $person->setFamilyName($cell);
                        break;
                    case 'C':
                        if (!empty($cell)) {
                            $nat = new Nationality();
                            $person->addNationality($nat);
                            $nat->setNricNumber(strtoupper($cell));
                            $countryCode = strtoupper(trim($row['G']));
                            $nat->setCountry(array_key_exists($countryCode, $countries)?$countries[$countryCode]:$countryCode);
                        }
                        break;
                    case 'D':
                        if (!empty($cell)) {
                            $io->note(trim($cell));
                            $dob = \DateTime::createFromFormat('d-m-Y', trim($cell));
                            $person->setBirthDate($dob);
                            $user->setBirthDate($dob);
                        }
                        break;
                    case 'E':
                        $person->setGender(strtoupper(trim($cell)));
                        break;
                }
            }

            $manager->persist($person);
            $manager->flush($person);
            $member = $org->getIndividualMemberByPerson($person);
            if (empty($member)) {
                $member = new IndividualMember();
                $member->setPerson($person);
                $member->setOrganisation($org);
                $manager->persist($member);
                $manager->flush($member);
            }
        }
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
