Steps for rPi:

Write the debian Stretch img to the card.
To enable ssh: touch boot:/ssh

Add user danny (add to pi groups).

Copy ssh authorized_keys file.

Get the latest dist/firmware
    apt-get update
    apt-get upgrade
    rpi-update

Install php and nginx
    apt install nginx php-fpm php-sqlite3 sqlite3 


Configure nginx to serve php
    vi /etc/nginx/sites-available/default

    configure index doc line:
        index index.html index.htm index.php;

    add:
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        }

    change fs perms...
        chown -R www-data:pi /var/www/html/ 
        chmod -R 770 /var/www/html/
        /etc/init.d/nginx restart

Install hostapd	
    apt-get install hostapd


Configure /etc/dhcpcd.conf, /etc/hostapd/hostapd.conf, /etc/default/hostapd
    /etc/dhcpcd.conf:
        interface wlan0
        static ip_address=192.168.100.200/24

    /etc/hostapd/hostapd.conf
        interface=wlan0
        driver=nl80211
        ssid=ffam-notaumatic
        hw_mode=g
        channel=8
        wmm_enabled=0
        macaddr_acl=0
        auth_algs=1
        ignore_broadcast_ssid=0
        wpa=2
        wpa_passphrase=citamuaton
        wpa_key_mgmt=WPA-PSK
        wpa_pairwise=TKIP
        rsn_pairwise=CCMP

    /etc/default/hostapd
        DAEMON_CONF="/etc/hostapd/hostapd.conf"

Install pure-ftpd:
    apt-get install pure-ftpd
    ln -s /etc/pure-ftpd/conf/PureDB /etc/pure-ftpd/auth/50pure
    echo no > /etc/pure-ftpd/conf/PAMAuthentication
    echo no > /etc/pure-ftpd/conf/UnixAuthentication
    echo "yes" > /etc/pure-ftpd/conf/CreateHomeDir
    echo "yes" > /etc/pure-ftpd/conf/ChrootEveryone

    pure-pw useradd html -u pi -g pi -d /var/www/ 
    # Set some sane permisions for /var/www/html
    pure-pw mkdb
    pure-pw show html

    Enable SSL.
    echo 1 > /etc/pure-ftpd/conf/TLS
    openssl req -x509 -nodes -days 730 -newkey rsa:2048 -keyout /etc/ssl/private/pure-ftpd.pem -out /etc/ssl/private/pure-ftpd.pem
    systemctl restart pure-ftpd

