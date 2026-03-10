<?php

declare(strict_types=1);


namespace App\Alonti\View;

class MailView
{
    public function heading($text)
    {
        echo '<h1 style="font-family: \'Nunito Sans\', sans-serif; font-size: 24px; font-weight: 700; margin: 0; Margin-bottom: 10px;">' .
            $text .
            '</h1>';
    }

    public function subheading($text)
    {
        echo '<h3 style="font-family: \'Nunito Sans\', sans-serif; font-size: 18px; font-weight: 700; margin: 0; Margin-bottom: 0;">' .
            $text .
            '</h3>';
    }

    public function link($text, $link)
    {
        return '<a style="font-size: 13px; font-weight: 700; line-height: 22px; margin: 9px 20px; text-decoration: underline;display: inline-block;" href="' .
            $link .
            '">' .
            $text .
            '</a>';
    }

    public function paragraph($text, $mb = 15)
    {
        echo '<p style="font-family: \'Nunito Sans\', sans-serif; font-size: 13px; font-weight: normal; margin: 0; margin-bottom: ' .
            $mb .
            'px;">' .
            $text .
            '</p>';
    }

    public function button($text, $link = '#', $nest = '')
    {
        echo '<p style="font-family: \'Nunito Sans\', sans-serif; font-size: 13px; font-weight: normal; margin: 0; margin-bottom: 30px;"><a class="button" style="font-size: 13px; font-weight: 700; line-height: 22px; padding: 9px 20px; background: #f2682b; text-decoration: none;display: inline-block; color:#fff;" href="' .
            $link .
            '">' .
            $text .
            '</a>' .
            $nest .
            '</p>';
    }

    public function mailTo($email)
    {
        $mailTolink = 'mailto:' . $email;
        echo '<a href="' . $mailTolink . '">' . $email . '</a>';
    }
}
