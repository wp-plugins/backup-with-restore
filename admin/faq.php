<div id="hmbkp-faq">
<p><strong>Where does WP Backup store the backup files?</strong></p>

<p>Backups are stored on your server in <code><?php echo hmbkp_path() ?></code>, you can change the directory.</p>

<p><strong>Important:</strong> By default WP Backup backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does means that your backup directory can get quite large.</p>

<p><strong>How do I restore my site from a backup?</strong></p>

<p>You need to download the latest backup file either by clicking download on the backups page or via <code>FTP</code>. <code>Unzip</code> the files and upload all the files to your server overwriting your site. You can then import the database using your hosts database management tool (likely <code>phpMyAdmin</code>).</p>


<p><strong>Does WP Backup back up the backups directory?</strong></p>

<p>No.</p>

<p><strong>I'm not receiving my backups by email</strong></p>

<p>Most servers have a filesize limit on email attachments, it's generally about 10mb. If your backup file is over that limit it won't be sent attached to the email, instead you should receive an email with a link to download the backup, if you aren't even receiving that then you likely have a mail issue on your server that you'll need to contact your host about.</p>

<p><strong>How many backups are stored by default?</strong></p>

<p>WP Backup stores the last 10 backups by default.</p>

<p><strong>How long should a backup take?</strong></p>

<p>Unless your site is very large (many gigabytes) it should only take a few minutes to perform a back up, if your back up has been running for longer than an hour it's safe to assume that something has gone wrong, try de-activating and re-activating the plugin, if it keeps happening, contact support.</p>

<p><strong>What do I do if I get the wp-cron error message</strong></p>

<p>The issue is that your <code>wp-cron.php</code> is not returning a <code>200</code> response when hit with a http request originating from your own server, it could be several things, most of the time it's an issue with the server / site and not with WP Backup.</p>

<p>Some things you can test are.</p>

<ul>
<li>Are scheduled posts working? (They use wp-cron too).</li>
<li>Are you hosted on Heart Internet? (wp-cron is known not to work with them).</li>
<li>If you click manual backup does it work?</li>
<li>Try adding <code>define( 'ALTERNATE_WP_CRON', true ); to your</code>wp-config.php`, do automatic backups work?</li>
<li>Is your site private (I.E. is it behind some kind of authentication, maintenance plugin, .htaccess) if so wp-cron won't work until you remove it, if you are and you temporarily remove the authentication, do backups start working?</li>
</ul>

<p>If you have tried all these then feel free to contact support.</p>

<p><strong>How to get WP Backup working in Heart Internet</strong></p>

<p>The script to be entered into the Heart Internet cPanel is: <code>/usr/bin/php5 /home/sites/yourdomain.com/public_html/wp-cron.php</code> (note the space between php5 and the location of the file). The file <code>wp-cron.php</code> <code>chmod</code> must be set to <code>711</code>.</p>
</div>