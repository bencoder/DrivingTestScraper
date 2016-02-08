<?php
namespace Bencoder\DrivingTest\Service;


class DateMailer
{
    /**
     * Emails a list of dates to the given email address
     *
     * @param string $email
     * @param string[] $dates
     */
    public function mail($email, $dates)
    {
        $message = \Swift_Message::newInstance();
        $message
            ->setSubject('Driving test dates found.')
            ->setFrom(array('noreply@example.com' => 'Driving Test Checker'))
            ->setTo(array($email))
            ->setBody(implode("\r\n", $dates));

        $transport = \Swift_MailTransport::newInstance();
        $transport->send($message);
    }
}