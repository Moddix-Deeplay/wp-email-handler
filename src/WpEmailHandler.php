<?php

namespace Moddix\WpEmailHandler;

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use function Env\env;

class WpEmailHandler
{
    /**
     * @var array{
     *     from_name: string,
     *     from_email: string,
     *     host: string,
     *     port: int,
     *     user: string,
     *     pass: string,
     *     secure: string,
     *     debug: string
     * }
     */
    private array $config = [];

    public function __construct()
    {
        $this->loadConfig();
        $this->setWpMailHeaders();
        $this->email();
    }

    private function loadConfig(): void
    {
        $config = [
            'from_name' => env('EMAIL_FROM_NAME'),
            'from_email' => env('EMAIL_FROM_EMAIL'),
            'host' => env('SMTP_HOST'),
            'port' => env('SMTP_PORT'),
            'user' => env('SMTP_USER'),
            'pass' => env('SMTP_PASS'),
            'secure' => env('SMTP_SECURE') ?: '', // 'tls' or 'ssl' or '' for insecure
            'debug' => intval(env('SMTP_DEBUG') ?: 0), // 0, 1, or 2
        ];

        $this->validateConfig($config);

        $this->config = $config;
    }

    private function validateConfig(array $config): void
    {
        $collectionConstraint = new Assert\Collection([
            'from_name' => new Assert\NotBlank(),
            'from_email' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
            'host' => new Assert\AtLeastOneOf([
                new Assert\Ip(),
                new Assert\Hostname(),
            ]),
            'port' => [
                new Assert\NotBlank(),
                new Assert\Range(min: 1, max: 65535),
            ],
            'user' => new Assert\NotBlank(),
            'pass' => new Assert\NotBlank(),
            'secure' => new Assert\Choice(['', 'tls', 'ssl']),
            'debug' => new Assert\Choice([0, 1, 2]),
        ]);

        $validator = Validation::createValidator();

        $violations = $validator->validate($config, $collectionConstraint);

        if (count($violations) > 0) {
            do_action('wonolog.log.error', ['message' => 'Invalid SMTP configuration', 'context' => [$violations]]);
            throw new \InvalidArgumentException('Invalid SMTP configuration');
        }
    }

    private function setWpMailHeaders(): void
    {
        add_filter('wp_mail_from_name', function ($original_name) {
            return $this->config['from_name'];
        });

        add_filter('wp_mail_from', function ($original_email) {
            return $this->config['from_email'];
        });
    }

    private function email(): void
    {
        add_action('phpmailer_init', function (PHPMailer $phpmailer) {
            $phpmailer->isSMTP();
            // Enforce strict TLS/SSL options for verify connections
            $phpmailer->SMTPOptions = [
                'ssl' =>
                    [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                        'allow_self_signed' => false
                    ]
            ];
            $phpmailer->Host = $this->config['host'];
            $phpmailer->Port = $this->config['port'];
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $this->config['user'];
            $phpmailer->Password = $this->config['pass'];
            $phpmailer->SMTPSecure = $this->config['secure'];
            $phpmailer->From = $this->config['from_email'];
            $phpmailer->FromName = $this->config['from_name'];
            $phpmailer->SMTPDebug = $this->config['debug'];
            $phpmailer->Debugoutput = function ($str, $level) {
                do_action('wonolog.log.debug', ['message' => $str]);
            };
        });
    }
}
