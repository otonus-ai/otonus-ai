# otonus.ai website

Everything in `public/` is the live site at https://otonus.ai (Namecheap cPanel hosting).
The site updates itself from this repo, the same way the Raavanaa Bowling site does:
`public/.updater/update.php` runs from cPanel cron every 5 minutes, and when `main` has a new commit it copies `public/` onto the site.
Files removed from `public/` are removed from the server too. Its log is `public_html/.updater/log.txt`.

- `src/page.html` is the page source. `python3 src/build.py` turns it into `public/index.html` (run it before committing; the server uses `public/index.html` as it is).
- `public/contact.php` emails contact-form enquiries to info@otonus.ai.
- `public/.htaccess` forces https://otonus.ai (no www) and sets caching.

## One-time setup on the hosting

1. Upload `otonus-site-upload.zip` to the otonus.ai `public_html` in cPanel File Manager and extract it.
2. In cPanel, open Cron Jobs, choose "Every 5 minutes", and add this command (File Manager shows the real home folder path at the top):
   `php -q /home/CPANELUSER/public_html/.updater/update.php >/dev/null 2>&1`

No passwords are stored anywhere: the repo is public, and the server only reads from it.
