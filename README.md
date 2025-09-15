Simple plugin for sending emails via SMTP using the PHPMailer library.  
Works with Bedrock, native WordPress is not tested.

- Uses Symfony Validator to validate configuration parameters.
- Uses the Wonolog library for logging (You do not need to install this dependency, if it is missing, log messages will simply not be recorded).
- The PHPMailer dependency is not specified because the `wp-includes/PHPMailer` class is used.
- Env dependency not present because Bedrock is assumed.

Use an `.env` file to configure the plugin. Example:

```
# Email settings
EMAIL_FROM_NAME='Website name'
EMAIL_FROM_EMAIL='noreply@example.com'
SMTP_SSL_VERIFY_PEER=false# 'true' or 'false'
SMTP_SSL_VERIFY_PEER_NAME=false# 'true' or 'false'
SMTP_SSL_ALLOW_SELF_SIGNED=true# 'true' or 'false'
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER='noreply@example.com'
SMTP_PASS='123456'
SMTP_SECURE='' # 'tls', 'ssl', or leave empty for no encryption
SMTP_DEBUG='0' # 0 = off (for production use), 1 = client messages, 2 = client and server messages
```
