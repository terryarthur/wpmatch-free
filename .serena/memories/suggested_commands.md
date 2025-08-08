Key commands:
- WordPress: Activate plugin via WP Admin > Plugins > WP Match Free
- PHPCS (after composer install): vendor/bin/phpcs --standard=WordPress --extensions=php .
- PHPCBF (auto-fix): vendor/bin/phpcbf --standard=WordPress --extensions=php .
- Generate POT (later): wp i18n make-pot . languages/wpmatch-free.pot
- Run WP-CLI (if available): wp plugin activate wpmatch-free