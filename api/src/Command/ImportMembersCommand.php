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
            ->addOption('clear-person', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('file');
        $arg2 = $input->getArgument('org');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
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
        $org = $orgRepo->find((int) $arg2);

        if (empty($org)) {
            throw new \Exception('Org not found');
        }

        if ($input->getOption('clear-person')) {
            $io->note('Emptying people for '.$org->getName());
            /** @var IndividualMember $member */
            foreach ($org->getIndividualMembers() as $member) {
                $person = $member->getPerson();
                $user = $person->getUser();
                $manager->remove($user);
                $manager->remove($person);
                $manager->remove($member);
            }
            $manager->flush();
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
            $companyName = $positionName = $email = $alternateName = null;
            $name = $outlet = $mobile = $dob = null;
            foreach ($row as $col => $cell) {
                switch ($col) {
                    case 'A':
//                        $person->setGivenName($cell);
                        break;
                    case 'B':
                        $outlet = $cell;
                        break;
                    case 'C':
                        $name = ($cell);
                        break;
                    case 'D':
                        $mobile = $cell;
                        if (!empty($cell)) {
//                            $nat = new Nationality();
//                            $person->addNationality($nat);
//                            $nat->setNricNumber(strtoupper($cell));
//                            $countryCode = strtoupper(trim($row['G']));
//                            $nat->setCountry(array_key_exists($countryCode, $countries)?$countries[$countryCode]:$countryCode);
                        }
                        break;
                    case 'E':
                        if (!empty($cell)) {
                            $io->note('DOB');
                            $io->note(trim($cell));
//                            $dob = $cell;
//                            $cell = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell);
                            $dob = \DateTime::createFromFormat('d/m/Y', trim($cell));
                        }
                        break;
                    case 'F':
                        if (!empty($cell)) {
                            $email = $cell;
                        }
                        break;
                    case 'G':
                        if (!empty($cell)) {
                            $companyName = $cell;
                        }
                        break;
                    case 'H':
                        if (!empty($cell)) {
                            $positionName = $cell;
                        }
                        break;
                    case 'I':
                        if (!empty($cell)) {
                            $alternateName = $cell;
                        }
                        break;
                }
            }

            if (empty($mobile) || empty($dob)) {
                continue;
            }

            $nric = $mobile.'_'.$dob->format('d-m-Y');


            $person = $personRepo->findOneByNricNumber($nric);
            $memberEmail = null;
            if (empty($person)) {
                $person = new Person();
                if (empty($email)) {
                    $email = $nric.'.nric.auto-gen@whatwechat.net';
                } else {
                    $memberEmail = $email;
                }
                $person->setEmail($email);
                $person->setPhoneNumber($mobile);
                $person->setAlternateName($alternateName);
                $person->setEmployerName($companyName);
                $person->setJobTitle($positionName);

                $user = new User();
                $user->setPlainPassword($nric);
                $user->setEmail($email);
                $user->setIdNumber($nric);
                $user->setPhone($nric);
                $person->setUser($user);
                $user->setPerson($person);
            }

            $person->setGivenName($name);

            $user = $person->getUser();
            $person->setBirthDate($dob);
            $user->setBirthDate($dob);

            $nat = new Nationality();
            $person->addNationality($nat);
            $nat->setNricNumber(strtoupper($nric));


            $manager->persist($person);
            $manager->flush($person);
            $member = $org->getIndividualMemberByPerson($person);

            if (empty($member)) {
                $member = new IndividualMember();
            }
            if (!empty($memberEmail)) {
                $member->setEmail($memberEmail);
            }
            $member->setGroupName($outlet);
            $member->setPerson($person);
            $member->setOrganisation($org);
            $manager->persist($member);
            $manager->flush($member);
        }
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
