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
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_OPTIONAL,
                'A date to compare against, will return only dates less than this'
            )
            ->addOption(
                'mail',
                'm',
                InputOption::VALUE_OPTIONAL,
                'If given, will email the results (if any)'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $licence = $input->getArgument('licence');
        $center  = $input->getArgument('center');
        $filterDate = $input->getOption('filter');
        $mail = $input->getOption('mail');

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
        $dates = $slots->each(function($node, $i) use($output) {
            return $node->text();
        });

        if ($filterDate) {
            $dates = $this->filterByDate($filterDate, $dates);
        }


        foreach($dates as $date) {
            $output->writeln($date);
        }

        if (count($dates) && $mail) {
            $this->mailDates($mail, $dates);
        }
    }

    /**
     * @param string $mail
     * @param string[] $dates
     */
    private function mailDates($mail, $dates)
    {
        $message = \Swift_Message::newInstance();
        $message
            ->setSubject('Driving test dates found.')
            ->setFrom(array('noreply@example.com' => 'Driving Test Checker'))
            ->setTo(array($mail))
            ->setBody(implode("\r\n", $dates));

        $transport = \Swift_MailTransport::newInstance();
        $transport->send($message);
    }

    /**
     * @param string $filterDate
     * @param string[] $dates
     * @return array
     */
    protected function filterByDate($filterDate, $dates)
    {
        $filterDateTime = new \DateTime($filterDate);
        $dates = array_filter($dates, function ($date) use ($filterDateTime) {
            return (new \DateTime($date)) <= $filterDateTime;
        });
        return $dates;
    }
}