<?php
namespace Bencoder\DrivingTest\Command;

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