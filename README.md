# otonus.ai website

Everything in `public/` is the live site at https://otonus.ai (Namecheap cPanel hosting).
Pushing to `main` deploys it automatically through `.github/workflows/deploy.yml`.
Only changed files are uploaded; files removed from `public/` are removed from the server too.

- `src/page.html` is the page source. `python3 src/build.py` turns it into `public/index.html` (the workflow runs this too).
- `public/contact.php` emails contact-form enquiries to info@otonus.ai.
- `public/.htaccess` forces https://otonus.ai (no www) and sets caching.

## One-time deploy setup

1. **cPanel FTP account.** In the otonus.ai cPanel, open FTP Accounts and create `deploy@otonus.ai` with directory `public_html` (clear the extra folder name cPanel suggests). Note the FTP server name shown under "Configure FTP Client" (looks like `server123.web-hosting.com`).
2. **Bitwarden Secrets Manager.** In Bitwarden, switch to Secrets Manager (it needs an organization; the free plan is enough). Create a project `website-deploys` and three secrets in it: `OTONUS_FTP_SERVER`, `OTONUS_FTP_USERNAME`, `OTONUS_FTP_PASSWORD`.
3. **Machine account.** In Secrets Manager, create a machine account `github-deploys`, give it *Can read* on the `website-deploys` project, and create an access token. Copy it once.
4. **GitHub.** In this repo, Settings > Secrets and variables > Actions:
   - Secrets tab: `BW_ACCESS_TOKEN` = the access token.
   - Variables tab: `BW_FTP_SERVER_ID`, `BW_FTP_USERNAME_ID`, `BW_FTP_PASSWORD_ID` = the IDs of the three Bitwarden secrets (each secret's ID is shown in its row; IDs are not sensitive).
5. Run the workflow once from the Actions tab (Deploy to cPanel > Run workflow).

The first run uploads everything; later runs upload only what changed.

## Adding another site

Copy `.github/workflows/deploy.yml` into that site's repo, create a separate FTP account in that site's cPanel, add its three secrets to the same Bitwarden project, and set the same secret and variables in the new repo. The same machine account token works for every site.
