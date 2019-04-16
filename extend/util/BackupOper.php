<?php

namespace util;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class BackupOper
{
    public static function getMail()
    {
        // Passing `true` enables exceptions
        $mail = new PHPMailer(true);
        //Server settings
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.mxhichina.com';                   // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'postmaster@zxyqwe.com';                 // SMTP username
        $mail->Password = config('amail_sk');                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        //Recipients
        $mail->setFrom('postmaster@zxyqwe.com', 'AutoMail');
        $mail->isHTML(false);
        return $mail;
    }

    public static function run()
    {
        $today = date('Ymd');
        if (cache("?BackupOper$today")) {
            return;
        }
        cache("BackupOper$today", $today, 86400);
        $obj = [
            config('backup_dir') . DS . "hanbj.$today.sql.gz",
            config('backup_dir') . DS . "wiki.$today.sql.gz"
        ];
        $mail = self::getMail();
        try {
            $mail->addAddress(config('backup_dst'));

            //Content
            $mail->Subject = "Backup $today";

            $body = '';
            foreach ($obj as $item) {
                $mail->addAttachment($item);
                $body .= "$item : " . filesize($item) . "\r\n";
            }
            $mail->Body = $body;

            $mail->send();
            trace("备份邮件", MysqlLog::INFO);
        } catch (Exception $e) {
            trace("备份邮件 " . $mail->ErrorInfo, MysqlLog::ERROR);
        }
    }
}