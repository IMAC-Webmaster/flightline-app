## Steps for installing and configuring a rPi for FlightLine:

1. [Introduction](#introduction)
1. [Prepare the SD](#prepare-sd-card)
1. [Change the password](#change-the-default-password)
1. [Create local admin user](#create-local-admin-user)
1. [Get the latest dist/firmware](#get-the-latest-dist/firmware)
1. [Install Docker CE](#install-docker-ce)
1. [Install command line utilities](#install-command-line-utilities)
1. [Install Flightline](#install-flightline)
1. [Set the timezone](#set-the-timezone)
1. [Configure WiFi](#configure-wifi)

## Introduction
FlightLine is a small web app that is intended to be a lightweight alternative to NotauScore - the sofware that supports NotauMatic devices used for scoring F3A and Pattern contests.

It is specifically created to allow for the results to be downloaded by Score! (the application used for scoring IMAC competitions).

FlightLine's aim is to (eventually) also support other devices such as Peter Vogel's iOS based Electronic Scribe app as well as ScorePad written by Dan Carroll.

## Prepare SD Card
Write the Raspberian image to the card.
You can get it here: https://www.raspberrypi.com/software/

When the image is written and mounted, the boot Fat32 partition should be seen.
To enable ssh, create an empty file called ssh in the root folder of this partition.
On a mac:

    <Laika:danny> 07:48 ~ : touch /Volumes/boot/ssh
    <Laika:danny> 07:48 ~ :

On windows, create the file in windows explorer.  Be carefull not to create a file called ssh.txt or with some other extension.   It should be simply "ssh".
If you wish to do it in the command line of windows, you can with the command ```type nul > W:\ssh```.

The following example assumes the 'boot' drive is drive W:


    Microsoft Windows [Version 10.0.19043.1466]
    (c) Microsoft Corporation. All rights reserved.
    
    C:\Users\danny>type nul > w:\ssh
    
    C:\Users\danny>dir w:\
    
     Volume in drive W is boot
     Volume Serial Number is 1EDA-F965
    
     Directory of W:\
    
    11/02/2022  01:31 AM    <DIR>          .
    11/02/2022  01:31 AM    <DIR>          ..
    11/02/2022  01:31 AM                 0 ssh
                   1 File(s)              0 bytes
                   2 Dir(s)  71,592,923,136 bytes free
    
    W:\>

You can now eject the SD Card and put it into your rPi hardware and reboot.

When it is available, log into the rPi via ssh.  If you cannot find the IP address, it's displayed on the console via HDMI at the end of the boot process.

## A note on SSH and the unix command line
Raspberry Pi OS is just another variant of Unix/Linux (it's based on Debian).   There's a lot of stuff that needs to be done on the Unix command line.    It can be sometimes confusing to know exactly what to type.

You can access the command line interface via the desktop if you installed that version of Raspberry Pi OS, by running the 'Terminal' application.
Otherwise, from your laptop you can connect to the rPi via SSH client software.

For Mac, this is already available running 'ssh' from the terminal application., 

For Windows, I'd recommend either 'puTTY' from https://www.putty.org/ or 'MobaXTerm' from https://mobaxterm.mobatek.net/

Both are excellent, with MobaXTerm also including a nice file transfer interface as well as simply the SSH interface.

To that end, if you are unfamiliar with unix, I'd suggest following this tutorial from the Ubuntu website on unix command line basics.

https://ubuntu.com/tutorials/command-line-for-beginners

Whether you connect with ssh, putty, mobaxterm or terminal, you always end up with a 'shell' session.   That is, a command line prompt into the Unix OS.

The one thing I would add is information about the default raspberry pi shell prompt.
This is the first part of the line in the shell.

It looks a little like this:

    pi@raspberrypi:~ $

Lets break it down.   The format is as follows.

    <user>@<hostname>:<path><privilage>
    where:
        <user> is the username of the user you are currently running as.
        <hostname> is the name of the rPi.   (defaults to raspberrypi)
        <path> is where in the unix filesystem you are currently
               (~ is a shortcut for the home dir of the current user.)
        <privilege> is an indicater that you are either operating as a normal user ( $) or a super user (#).
               It is meant to give you a warning if you are a super user that you should be careful.

So when we see the following command:

    pi@raspberrypi:~ $ passwd

It means that my instruction expects you to be logged in as the pi user, in their home dir, and you should run the command ```passwd```.
If you don't see a prompt, that usually indicates the command's output.

The following set of commands means: Change to the root user, and run the command "uptime" to display some information about how long the rPi has been powered on.

    pi@raspberrypi:~ $ sudo su -
    root@raspberrypi:~# uptime
    01:15:02 up  1:21,  2 users,  load average: 0.00, 0.00, 0.00

Notice how there was no output from the 'sudo' command, but the prompt changed to the root user?   The uptime command told us how long the machine was 'up', the number of logged in users, and the load averages for the last 1, 5 and 15 minutes (0.00 means the pi is not really working at all).

So if you are logged in as the pi user and need to be the root user, the command ```sudo su -``` will do that for you.

## Change the default password

    pi@raspberrypi:~ $ passwd
    Changing password for pi.
    Current password: 
    New password: 
    Retype new password: 
    passwd: password updated successfully
    pi@raspberrypi:~ $

Note: Changing the password with the passwd command, you wont see the passwords as you type them.
## Create local admin user

In this case I used username 'danny' and user description (full name) 'Dan Carroll'

    pi@raspberrypi:~ $ sudo su -
    
        Wi-Fi is currently blocked by rfkill.
        Use raspi-config to set the country before use.
    
    root@raspberrypi:~# USERNAME=danny
    root@raspberrypi:~# USERDESC="Dan Carroll"
    root@raspberrypi:~# useradd -c "${USERDESC}" -s /bin/bash -U -m -G sudo "${USERNAME}"
    root@raspberrypi:~# passwd "${USERNAME}"
    New password: 
    Retype new password: 
    passwd: password updated successfully
    root@raspberrypi:~# 

Notice that there is an error about the wifi.    That is because we have not configured it yet.
We'll do that later on.

If you have a ssh keypair you can add the public key to the ~/.ssh/authorized_keys file.

The directory .ssh should be created with mode 750 and the file should be 600.   If you don't have a key or don't know, then you can skip this part.

## Get the latest dist/firmware

    root@raspberrypi:~# apt-get update
    <SNIP!>
    root@raspberrypi:~# apt-get upgrade
    <SNIP!>

## Install Docker CE

    root@raspberrypi:~# curl -sSL https://get.docker.com | sh
    # Executing docker install script, commit: 442e66405c304fa92af8aadaa1d9b31bf4b0ad94
    + sh -c apt-get update -qq >/dev/null
    + sh -c DEBIAN_FRONTEND=noninteractive apt-get install -y -qq apt-transport-https ca-certificates curl >/dev/null
    + sh -c curl -fsSL "https://download.docker.com/linux/raspbian/gpg" | apt-key add -qq - >/dev/null
    Warning: apt-key output should not be parsed (stdout is not a terminal)
    + sh -c echo "deb [arch=armhf] https://download.docker.com/linux/raspbian buster stable" > /etc/apt/sources.list.d/docker.list
    + sh -c apt-get update -qq >/dev/null
    + [ -n  ]
    + sh -c apt-get install -y -qq --no-install-recommends docker-ce >/dev/null
    + sh -c docker version
    Client: Docker Engine - Community
     Version:           19.03.7
     API version:       1.40
     Go version:        go1.12.17
     Git commit:        7141c19
     Built:             Wed Mar  4 01:55:10 2020
     OS/Arch:           linux/arm
     Experimental:      false
    
    Server: Docker Engine - Community
     Engine:
      Version:          19.03.7
      API version:      1.40 (minimum version 1.12)
      Go version:       go1.12.17
      Git commit:       7141c19
      Built:            Wed Mar  4 01:49:01 2020
      OS/Arch:          linux/arm
      Experimental:     false
     containerd:
      Version:          1.2.13
      GitCommit:        7ad184331fa3e55e52b890ea95e65ba581ae3429
     runc:
      Version:          1.0.0-rc10
      GitCommit:        dc9208a3303feef5b3839f4323d9beb36df0a9dd
     docker-init:
      Version:          0.18.0
      GitCommit:        fec3683
        
    If you would like to use Docker as a non-root user, you should now consider adding your user to the "docker" group with something like:
    
      sudo usermod -aG docker your-user
    
    Remember that you will have to log out and back in for this to take effect!
    
    WARNING: Adding a user to the "docker" group will grant the ability to run
         containers which can be used to obtain root privileges on the
         docker host.
         Refer to https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface
         for more information.
    root@raspberrypi:~# sudo usermod -aG docker pi
    root@raspberrypi:~# sudo usermod -aG docker danny

## Install Docker Compose V2

    root@raspberrypi:~# curl -SL https://github.com/docker/compose/releases/download/v2.2.3/docker-compose-linux-aarch64 -o /usr/libexec/docker/cli-plugins/docker-compose
        % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
        Dload  Upload   Total   Spent    Left  Speed
        100   665  100   665    0     0   1286      0 --:--:-- --:--:-- --:--:--  1286
        100 23.3M  100 23.3M    0     0  3820k      0  0:00:06  0:00:06 --:--:-- 5005k
    
    root@raspberrypi:~# chmod +x /usr/libexec/docker/cli-plugins/docker-compose
    
    # Test the install...
    root@raspberrypi:~# docker compose version
    Docker Compose version v2.2.3

## Install command line utilities

Some utilities are necessary to get things working.
We want this as minimal as possible so that the upgrade list is the smallest impact.

    root@raspberrypi:~# apt-get install -y git sqlite3 composer
    Reading package lists... Done
    Building dependency tree
    Reading state information... Done
    .
    .
    <SNIP! - Lots of text removed>
    .
    .
    Processing triggers for man-db (2.8.5-2) ...

## Install Flightline

There are 3 containers needed for flightline.   Together they make up the flightline 'service'.   Currently the DB connection is via sqlite to a file.   This seems to be working out OK, but if it becomes a problem, then creating a DB instance wont be difficult.

This service resides in /data/flightline and is created by grabbing the git repository for 'Flightline'.

The 3 containers are:

- **Proxy**
  A nginx instance that is bound to ports 80 and 443 on the host and reverse-proxies the requests to the backend webserver.    This allows for fine-grained control of the web traffic and logging of the requests of the client (before any API translation).
- **Web**
  The nginx instance that handles the web requests for static files and hands off php requests to the PHP container via php-fpm.
- **Php**
  The php container is only for processing php files.   Separating this container allows for easy upgrading of php.


    root@raspberrypi:~# mkdir -p /data/volumes
    
    root@raspberrypi:~# cd /data/
    
    root@raspberrypi:/data# git clone https://git.dannysplace.net/scm/score/flightline.git
    Cloning into 'flightline'...
    remote: Counting objects: 269, done.
    remote: Compressing objects: 100% (249/249), done.
    remote: Total 269 (delta 76), reused 0 (delta 0)
    Receiving objects: 100% (269/269), 48.15 KiB | 666.00 KiB/s, done.
    Resolving deltas: 100% (76/76), done.
      
    root@raspberrypi:/data# git clone https://git.dannysplace.net/scm/score/score-flightline-node.git
    Cloning into 'score-flightline-node'...
    remote: Counting objects: 5844, done.
    remote: Compressing objects: 100% (5730/5730), done.
    remote: Total 5844 (delta 2724), reused 182 (delta 80)
    Receiving objects: 100% (5844/5844), 9.40 MiB | 1.30 MiB/s, done.
    Resolving deltas: 100% (2724/2724), done.
    Checking out files: 100% (6500/6500), done.
    
    root@raspberrypi:/data# chown root:adm ./score-flightline-node
    root@raspberrypi:/data# chmod 2775 ./score-flightline-node
    root@raspberrypi:/data# ls -asl
    total 28
    4 drwxr-xr-x  7 root root 4096 Mar  8 08:17 .
    4 drwxr-xr-x 23 root root 4096 Mar  8 05:22 ..
    4 drwxr-xr-x  4 root root 4096 Mar  8 08:17 flightline
    4 drwxrwsr-x 10 root adm  4096 Mar  8 08:17 score-flightline-node
    4 drwxr-xr-x  2 root root 4096 Mar  8 08:14 volumes
    

Now link the html dir of the web server, back to the score-flightline-node repo dir.

    root@raspberrypi:/data# cd /data/volumes/
    
    root@raspberrypi:/data/volumes# ln -s ../score-flightline-node html
    
    root@raspberrypi:/data/volumes# ls -asl
    total 8
    4 drwxr-xr-x 2 root root 4096 Mar  8 08:21 .
    4 drwxr-xr-x 7 root root 4096 Mar  8 08:17 ..
    0 lrwxrwxrwx 1 root root   24 Mar  8 08:21 html -> ../score-flightline-node

There are some components of the web-app that are easily updated via composer.   At the start I built these into the repo directly to save complexity, but that does not seem to be the internet way.  Especially if other users might want to contribute in the future.  I will also decouple datatables/bootstrap/jquery for this same reason.

The first time you run the composer command it will download the docker container.
Then it will check composer.json and install the extra components in /vendor

    root@raspberrypi:/data/volumes# exit
    
    
    pi@raspberrypi:/data/volumes $ cd /data/score-flightline-node
    
    pi@raspberrypi:/data/score-flightline-node $ composer install
    Composer is operating significantly slower than normal because you do not have the PHP curl extension enabled.
    No lock file found. Updating dependencies instead of installing from lock file. Use composer update over composer install if you do not have a lock file.
    Loading composer repositories with package information
    Updating dependencies
    Lock file operations: 2 installs, 0 updates, 0 removals
      - Locking katzgrau/klogger (dev-master de2d3ab)
      - Locking psr/log (1.1.4)
        Writing lock file
        Installing dependencies from lock file (including require-dev)
        Package operations: 2 installs, 0 updates, 0 removals
      - Downloading psr/log (1.1.4)
      - Syncing katzgrau/klogger (dev-master de2d3ab) into cache
      - Installing psr/log (1.1.4): Extracting archive
      - Installing katzgrau/klogger (dev-master de2d3ab): Cloning de2d3ab677 from cache
        Generating autoload files

Now the proxy, web and php containers are essentially ready.   However there is no database yet.
Evetually this will be part of a web process, but for now, lets create it manually.

    pi@raspberrypi:/data/score-flightline-node $ sqlite3 db/flightline.db < include/dbCreate_v2.sql

Finally, before we can start the containers we must make sure that they can write to the DB and to the log dir.  It will also need to write to the api directory to create a secret token.

    pi@raspberrypi:/data/score-flightline-node $ sudo chown -R www-data:www-data log db
    pi@raspberrypi:/data/score-flightline-node $ sudo chgrp www-data api/[0-9]*
    pi@raspberrypi:/data/score-flightline-node $ sudo chmod 775 api/[0-9]*

Finally, start the service!

    pi@raspberrypi:/data/score-flightline-node $ cd /data/flightline/flightline
    pi@raspberrypi:/data/flightline/flightline $ docker compose up
    [+] Running 28/28
    ⠿ php Pulled                                                   32.8s
    ⠿ ffabeb2e77ed Pull complete                                   16.6s
    ⠿ 4aeae596b5e6 Pull complete                                   16.7s
    ⠿ ce05405c3f08 Pull complete                                   16.9s
    ⠿ d1c7579ea307 Pull complete                                   17.1s
    ⠿ 094b52a40f15 Pull complete                                   17.3s
    ⠿ 0a621354cfac Pull complete                                   17.5s
    ⠿ 0747d54d607d Pull complete                                   17.7s
    ⠿ 07bef3c029d6 Pull complete                                   17.8s
    ⠿ 01770dc7b2f1 Pull complete                                   18.0s
    ⠿ ddc85c2a4787 Pull complete                                   18.2s
    ⠿ 1e348220758b Pull complete                                   18.4s
    ⠿ dd40e1889052 Pull complete                                   24.4s
    ⠿ b5bca0f8795c Pull complete                                   24.6s
    ⠿ 0a18dd3db6b6 Pull complete                                   25.1s
    ⠿ 451aace03281 Pull complete                                   25.3s
    ⠿ 5077d88849fb Pull complete                                   27.2s
    ⠿ 49d569b8292e Pull complete                                   27.3s
    ⠿ 28a326bd3824 Pull complete                                   27.5s
    ⠿ 0def13e155ca Pull complete                                   27.6s
    ⠿ proxy Pulled                                                 47.4s
    ⠿ 6fba654dd4ee Pull complete                                   41.0s
    ⠿ 13b3c8cc8cde Pull complete                                   41.3s
    ⠿ 573040833908 Pull complete                                   41.5s
    ⠿ web Pulled                                                   47.4s
    ⠿ 8998bd30e6a1 Pull complete                                   38.8s
    ⠿ 661b4150d3a3 Pull complete                                   41.2s
    ⠿ 5de8f8b958d2 Pull complete                                   41.7s
    [+] Running 8/8
    ⠿ Network flightline_default       Created                      0.1s
    ⠿ Volume "flightline_proxy_certs"  Created                      0.0s
    ⠿ Volume "flightline_html"         Created                      0.0s
    ⠿ Volume "flightline_web_confd"    Created                      0.0s
    ⠿ Volume "flightline_proxy_confd"  Created                      0.0s
    ⠿ Container flightline-php-1       Created                     24.7s
    ⠿ Container flightline-web-1       Created                      0.2s
    ⠿ Container flightline-proxy-1     Created

At this point, the containers look like they are running.    But the container is running inthe foreground and we need to run it in the background.

If you hit ctrl-c then the container will stop.

    ^CGracefully stopping... (press Ctrl+C again to force)
    [+] Running 3/3
    ⠿ Container flightline-proxy-1  Stopped                        0.6s
    ⠿ Container flightline-web-1    Stopped                        0.5s
    ⠿ Container flightline-php-1    Stopped                       10.5s
    canceled

Now if we stop the whole service we can restart it in the background.    The -d option means detach..

    pi@raspberrypi:/data/flightline/flightline $ docker compose down
    [+] Running 4/4
    ⠿ Container flightline-proxy-1  Removed                                                                        0.6s
    ⠿ Container flightline-web-1    Removed                                                                        0.5s
    ⠿ Container flightline-php-1    Removed                                                                       10.5s
    ⠿ Network flightline_default    Removed                                                                        0.1s
    pi@raspberrypi:/data/flightline/flightline $ docker compose up -d
    [+] Running 4/4
    ⠿ Network flightline_default    Created                                                                        0.1s
    ⠿ Container flightline-php-1    Started                                                                        1.6s
    ⠿ Container flightline-web-1    Started                                                                        2.6s
    ⠿ Container flightline-proxy-1  Started                                                                        3.7s

We can read the logs with the logs command:

    pi@raspberrypi:/data/flightline/flightline $ docker compose logs -f
        flightline-web-1    | /docker-entrypoint.sh: /docker-entrypoint.d/ is not empty, will attempt to perform configuration
        flightline-web-1    | /docker-entrypoint.sh: Looking for shell scripts in /docker-entrypoint.d/
        flightline-web-1    | /docker-entrypoint.sh: Launching /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
        flightline-php-1    | [11-Feb-2022 02:32:52] NOTICE: fpm is running, pid 1
        flightline-php-1    | [11-Feb-2022 02:32:52] NOTICE: ready to handle connections
        flightline-web-1    | 10-listen-on-ipv6-by-default.sh: info: /etc/nginx/conf.d/default.conf is not a file or does not exist
        flightline-web-1    | /docker-entrypoint.sh: Launching /docker-entrypoint.d/20-envsubst-on-templates.sh
        flightline-web-1    | /docker-entrypoint.sh: Launching /docker-entrypoint.d/30-tune-worker-processes.sh
        flightline-web-1    | /docker-entrypoint.sh: Configuration complete; ready for start up
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: using the "epoll" event method
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: nginx/1.21.6
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: built by gcc 10.2.1 20210110 (Debian 10.2.1-6)
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: OS: Linux 5.10.92-v8+
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: getrlimit(RLIMIT_NOFILE): 1048576:1048576
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: start worker processes
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: start worker process 22
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: start worker process 23
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: start worker process 24
        flightline-web-1    | 2022/02/11 02:32:53 [notice] 1#1: start worker process 25
        flightline-proxy-1  | /docker-entrypoint.sh: /docker-entrypoint.d/ is not empty, will attempt to perform configuration
        flightline-proxy-1  | /docker-entrypoint.sh: Looking for shell scripts in /docker-entrypoint.d/
        flightline-proxy-1  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
        flightline-proxy-1  | 10-listen-on-ipv6-by-default.sh: info: /etc/nginx/conf.d/default.conf is not a file or does not exist
        flightline-proxy-1  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/20-envsubst-on-templates.sh
        flightline-proxy-1  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/30-tune-worker-processes.sh
        flightline-proxy-1  | /docker-entrypoint.sh: Configuration complete; ready for start up

The -f option follows the logs while omitting it will show all available logs then exit.

## Set the timezone

```
ToDo
```

## Configure WiFi

```
ToDo
```

