<?php

namespace Moddix\WpEmailHandler;

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Env\Env;

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
            'from_name' => Env::get('EMAIL_FROM_NAME'),
            'from_email' => Env::get('EMAIL_FROM_EMAIL'),
            'ssl' => [
                'verify_peer' => Env::get('SMTP_SSL_VERIFY_PEER') ?? true,
                'verify_peer_name' => Env::get('SMTP_SSL_VERIFY_PEER_NAME') ?? true,
                'allow_self_signed' => Env::get('SMTP_SSL_ALLOW_SELF_SIGNED') ?? false,
            ],
            'host' => Env::get('SMTP_HOST'),
            'port' => Env::get('SMTP_PORT'),
            'user' => Env::get('SMTP_USER'),
            'pass' => Env::get('SMTP_PASS'),
            'secure' => Env::get('SMTP_SECURE') ?? '', // 'tls' or 'ssl' or '' for insecure
            'debug' => Env::get('SMTP_DEBUG') ?? 0, // 0, 1, or 2
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
            'ssl' => new Assert\Collection([
                'verify_peer' => new Assert\Type('bool'),
                'verify_peer_name' => new Assert\Type('bool'),
                'allow_self_signed' => new Assert\Type('bool'),
            ]),
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
                        'verify_peer' => $this->config['ssl']['verify_peer'],
                        'verify_peer_name' => $this->config['ssl']['verify_peer_name'],
                        'allow_self_signed' => $this->config['ssl']['allow_self_signed'],
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
