<?php
// Keeps this website in step with GitHub, like the Raavanaa Bowling plugin.
// cPanel cron runs it every 5 minutes. When main has a new commit, it downloads
// the repo and copies public/ over the site folder, and removes files that the
// previous update put there but the new commit no longer has.
// Cron command (cPanel, every 5 minutes): php -q /home/CPANELUSER/public_html/.updater/update.php
const REPO = 'otonus-ai/otonus-ai';
const BRANCH = 'main';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$here = __DIR__;
$site = dirname($here);
$stateFile = "$here/state.json";
$logFile = "$here/log.txt";

function logline($msg) {
  global $logFile;
  $lines = is_file($logFile) ? array_slice(file($logFile), -199) : [];
  $lines[] = gmdate('Y-m-d H:i:s') . " $msg\n";
  file_put_contents($logFile, implode('', $lines));
}
function fetch($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 120, CURLOPT_USERAGENT => 'otonus-site-updater']);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $code === 200 ? $body : false;
}
function rrmdir($d) {
  if (!is_dir($d)) return;
  foreach (scandir($d) as $f) {
    if ($f === '.' || $f === '..') continue;
    is_dir("$d/$f") ? rrmdir("$d/$f") : unlink("$d/$f");
  }
  rmdir($d);
}
function listFiles($root, $prefix = '') {
  $out = [];
  foreach (scandir("$root/$prefix") as $f) {
    if ($f === '.' || $f === '..') continue;
    $rel = ltrim("$prefix/$f", '/');
    if (is_dir("$root/$rel")) $out = array_merge($out, listFiles($root, $rel));
    else $out[] = $rel;
  }
  return $out;
}

$lock = fopen("$here/.lock", 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit;

$state = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : ['sha' => '', 'files' => []];

// The git refs endpoint is not rate limited the way the GitHub API is.
$refs = fetch('https://github.com/' . REPO . '.git/info/refs?service=git-upload-pack');
if ($refs === false || !preg_match('/([0-9a-f]{40}) refs\/heads\/' . BRANCH . '\b/', $refs, $m)) {
  logline('could not read the latest commit from GitHub');
  exit(1);
}
$sha = $m[1];
if ($sha === $state['sha'] && !in_array('--force', $argv ?? [])) exit;

$tmp = sys_get_temp_dir() . '/otonus-update-' . getmypid();
@mkdir($tmp, 0700, true);
$zipData = fetch('https://codeload.github.com/' . REPO . "/zip/$sha");
$zip = new ZipArchive();
if ($zipData === false || file_put_contents("$tmp/src.zip", $zipData) === false || $zip->open("$tmp/src.zip") !== true) {
  logline("download of $sha failed");
  rrmdir($tmp);
  exit(1);
}
$zip->extractTo("$tmp/x");
$zip->close();
$top = glob("$tmp/x/*", GLOB_ONLYDIR)[0] ?? '';
$src = "$top/public";
if (!is_file("$src/index.html")) {
  logline("commit $sha has no public/index.html; site left as it was");
  rrmdir($tmp);
  exit(1);
}

$files = listFiles($src);
foreach ($files as $rel) {
  $dest = "$site/$rel";
  if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
  if (!is_file($dest) || md5_file($dest) !== md5_file("$src/$rel")) copy("$src/$rel", $dest);
}
foreach (array_diff($state['files'], $files) as $gone) {
  if (strpos($gone, '..') === false && is_file("$site/$gone")) unlink("$site/$gone");
}
file_put_contents($stateFile, json_encode(['sha' => $sha, 'files' => array_values($files)]));
logline('updated to ' . substr($sha, 0, 7) . ' (' . count($files) . ' files)');
rrmdir($tmp);
