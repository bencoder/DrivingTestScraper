<?php
namespace Bencoder\DrivingTest\Command;

use Goutte\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('check earliest availability')
            ->addArgument(
                'licence',
                InputArgument::REQUIRED,
                'driving licence to check'
            )
            ->addArgument(
                'center',
                InputArgument::REQUIRED,
                'Test Center/postcode (will take first match)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $licence = $input->getArgument('licence');
        $center  = $input->getArgument('center');

        $client = new Client();
        $crawler = $client->request('GET','https://driverpracticaltest.direct.gov.uk/application');
        $output->writeln('Step 1');
        $form = $crawler->selectButton('testTypeCar')->form();

        $crawler = $client->submit($form);
        $output->writeln('Step 2');
        $form = $crawler->selectButton('drivingLicenceSubmit')->form();
        $form->setValues([
            'driverLicenceNumber' => $licence,
            'extendedTest' => 'false',
            'specialNeeds' => 'false'
        ]);

        $crawler = $client->submit($form);
        $output->writeln('Step 3');
        $form = $crawler->selectButton('testCentreSubmit')->form();
        $form->setValues(
            ['testCentreName' => $center]
        );

        $crawler = $client->submit($form);
        $output->writeln('Step 4');
        $link = $crawler->filter('.test-centre-results > li > a')->first()->link();

        $crawler = $client->click($link);
        $output->writeln('Step 5');

        $button = $crawler->selectButton('drivingLicenceSubmit');
        if ($button->count() == 0) {
            $output->writeln('Captcha!');
            //TODO: display captcha image and ask to solve? Use decaptcha?
            return;
        }
        $form = $button->form();
        $date = (new \DateTime())->format('d/m/y');
        $form->setValues(
            ['preferredTestDate' => $date]
        );

        $crawler = $client->submit($form);
        $output->writeln('Step 6');

        $slots = $crawler->filter('.slotDateTime');
        foreach($slots as $slot) {
            $output->writeln($slot->html());
        }
    }
}