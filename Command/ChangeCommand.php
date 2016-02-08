<?php
namespace Bencoder\DrivingTest\Command;

use Bencoder\DrivingTest\Service\DateFilter;
use Bencoder\DrivingTest\Service\DateMailer;
use Goutte\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('change')
            ->setDescription('check earliest availability for an existing test')
            ->addArgument(
                'licence',
                InputArgument::REQUIRED,
                'driving licence to check'
            )
            ->addArgument(
                'reference',
                InputArgument::REQUIRED,
                'Application reference number'
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
        $reference  = $input->getArgument('reference');
        $filterDate = $input->getOption('filter');
        $mail = $input->getOption('mail');

        $client = new Client();
        $crawler = $client->request('GET','https://driverpracticaltest.direct.gov.uk/login');
        $output->writeln('Step 1');
        $form = $crawler->selectButton('booking-login')->form();
        $form->setValues([
            'username' => $licence,
            'password' => $reference
        ]);

        $crawler = $client->submit($form);
        $output->writeln('Step 2');
        $link = $crawler->filter('#date-time-change')->first()->link();
        $crawler = $client->click($link);
        $output->writeln('Step 3');

        $button = $crawler->selectButton('drivingLicenceSubmit');
        if ($button->count() == 0) {
            $output->writeln('Captcha!');
            //TODO: display captcha image and ask to solve? Use decaptcha?
            return;
        }
        $form = $button->form();
        $crawler = $client->submit($form);
        $output->writeln('Step 4');

        $slots = $crawler->filter('.slotDateTime');
        $dates = $slots->each(function($node, $i) use($output) {
            return $node->text();
        });

        if ($filterDate) {
            $filter = new DateFilter();
            $dates = $filter->filterDates($dates, $filterDate);
        }

        foreach($dates as $date) {
            $output->writeln($date);
        }

        if (count($dates) && $mail) {
            $mailer = new DateMailer();
            $mailer->mail($mail,$dates);
        }
    }
}