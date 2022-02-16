# OcoMon - version 3.3
## Date: 2021, April
## Author: Flávio Ribeiro (flaviorib@gmail.com)

## License: GPLv3


IMPORTANT:
-----------

If you want to install OcoMon on your own, you need to know what a WEB server is and be familiar with the generic process of installing WEB systems.

To install OcoMon it is necessary to have an account with permission to create databases on MySQL or MariaDB and write access to the public folder of your web server.

Before starting the installation process, read this file to the end. 


REQUIREMENTS:
------------

+ Web-server with PHP + Apache + MySQL (or MariaDB):
    
    - PHP at least from version **7.x** with:
        - **mbstring**
        - **PDO**
        - **openssl**
    
    - MySQL at least from version 5x or MariaDB:

<br>

INSTALLATION OR UPDATE IN A PRODUCTION ENVIRONMENT: 
--------------------------------------------------

<br>
## IMPORTANT (in case of update)

+ Firstly, identify which is **your installed version**. After that, go straight to the corresponding section to update your specific version. For each base version there are **only one specific file** to be imported into your database. 

<br>
### Update:

#### If your current version is the 3.2 or 3.1 or 3.1.1:

1. Import the database update file "05-DB-UPDATE-FROM-3.2.sql" (in install/3.x/) : <br>

        Terminal command line:
        mysql -u root -p [database_name] < /path/to/ocomon_3.3/install/3.x/04-DB-UPDATE-FROM-3.2.sql
        
        Where: [database_name]: It is the name of the OcoMon database

2. Overwrite your current version's scripts with version 3.3 scripts. Done!<br>


#### If your current version is the 3.0 (final release):

1. Import the database update file "04-DB-UPDATE-FROM-3.0.sql" (in install/3.x/) : <br>

        Terminal command line:
        mysql -u root -p [database_name] < /path/to/ocomon_3.3/install/3.x/04-DB-UPDATE-FROM-3.0.sql
        
        Where: [database_name]: It is the name of the OcoMon database

2. Overwrite your current version's scripts with version 3.3 scripts. Done!<br>


#### If your current version is any of the release candidates (rc) of version 3.0 (rc1, rc2, rc3):

+ It is always recommended to perform **BACKUP** of both the version scripts and the database currently in use by the system.

+ Import the database update file "03-DB-UPDATE-FROM-3.0rcx.sql" (in install/3.x/) : 

        Terminal command line:
        mysql -u root -p [database_name] < /path/to/ocomon_3.3/install/3.x/03-DB-UPDATE-FROM-3.0rcx.sql
        
        Where: [database_name]: It is the name of the OcoMon database

+ Overwrite your current version's scripts with version 3.3 scripts. Done! <br>


        
#### If your current version is the version 2.0 final

+ **IMPORTANT:** Carefully read the changelog-3.0.md file (*in /changelog*) to check the news and especially about **functions removed from previous versions** and some new **necessary settings** as well as counting time changes from SLAs to pre-existing tickets. 

+ Perform the **BACKUP** of both the version scripts and the database currently in use by the system. 

+ The update process considers the current version to be 2.0 (final release), so if your version is earlier, update it to version 2.0 first. 

+ To update from version 2.0 (final release), just overwrite the scripts in your OcoMon folder with the scripts from the new version and import the update db file into MySQL: 02-DB-UPDATE-FROM-2.0.sql (in /install/3.x/). <br><br>

        Terminal command line:
        mysql -u root -p [database_name] < /path/to/ocomon_3.3/install/3.x/02-DB-UPDATE-FROM-2.0.sql
    
        Where: [database_name]: It is the name of the OcoMon database


### First installation:

The installation process is very simple and can be done by following 3 steps:

1. **Install system scripts:**

    Unpack the contents of the OcoMon_3.3 package in the public directory of your web server (*the path may vary depending on the distribution or configuration, but in general it is usually **/var/www/html/***).

    File permissions can be your server's default.

2. **Creation of the database:**<br>

    **LOCALHOST SYSTEM** (If your system will be installed on an external server jump to the section [EXTERNAL HOSTING SYSTEM ]):
    
    To create the entire datebase of OcoMon, you need to import a single file of SQL statements:
    
    The file is:
    
        01-DB_OCOMON_3.3-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql (in /install/3.x/).

    Terminal command line:
        
        mysql -u root -p < /path/to/ocomon_3.3/install/3.x/01-DB_OCOMON_3.3-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql
        
    The system will ask for the password of the MySQL root user (or any other user that was provided instead of root in the above command).

    The above command will create the user "ocomon_3" with the default password "senha_ocomon_mysql", and the database "ocomon_3".

    **It is important to change this password for the user "ocomon_3" in MySQL right after installing the system.**

    You can also import the SQL file using any other database manager of your choice.


    If you want the database to have another name (instead of "ocomon_3"), edit directly in the file (*identify the entries related to the database name and also the user password at the beginning of the file*):

    "01-DB_OCOMON_3.3-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql"

    Before importing it. Use this same new information in the system settings file (step **3**) .
    
    **After importing, it is recommended to delete the "install" folder.**<br>


    **EXTERNAL HOSTING SYSTEM:**

    In this case, due to possible limitations for naming databases and users (usually the provider stipulates a prefix for databases and users), it is recommended to use the username provided by the hosting service itself or create a specific user (if your user account allows it) directly through your database access interface. Therefore:

    - **create** a specific database for OcoMon (you define the name);
    - **create** a specific user to access the OcoMon database (or use your default user);
    - **Edit** the script "01-DB_OCOMON_3.3-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql" **removing** the following lines from the beginning of the file:

            CREATE DATABASE /*!32312 IF NOT EXISTS*/`ocomon_3` /*!40100 DEFAULT CHARACTER SET utf8 */;

            CREATE USER 'ocomon_3'@'localhost' IDENTIFIED BY 'senha_ocomon_mysql';
            GRANT SELECT , INSERT , UPDATE , DELETE ON `ocomon_3` . * TO 'ocomon_3'@'localhost';
            GRANT Drop ON ocomon_3.* TO 'ocomon_3'@'localhost';
            FLUSH PRIVILEGES;

            USE `ocomon_3`;

    - After that just import the changed file and continue with the installation process.

            mysql -u root -p [database_name] < /path/to/ocomon_3.3/install/3.x/01-DB_OCOMON_3.3-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql

        Where: [database_name] is the name of the database that was manually created.<br>



3. **Create the settings file:**

    Make a copy of the file config.inc.php-dist (*/includes/*) and rename it to config.inc.php. In this new file, check the information related to the database connection (*dbserver, database name, user and password*). <br><br>


TEST VERSION:
-------------

If you want to test the system before installing, you can run a Docker container with the system already working with some data already populated. If you already have Docker installed, then just run the following command on your terminal: 

        docker run -it --name ocomon_3.3 -p 8000:80 flaviorib/ocomon_demo-3.3:3.3 /bin/ocomon

Then just open your browser and access the following address:

        localhost:8000

And ready! You already have an installation of OcoMon ready for testing with the following registered users:<br>


| user      | Pass      | Description                         |
| :-------- | :-------- | :---------------------------------  |
| admin     | admin     | System administration level         |
| operador  | operador  | Standard operator - level 1         |
| operador2 | operador  | Standard operator - level 2         |
| abertura  | abertura  | Only for tickets opening            |


If you don't have Docker, go to the website and install the version for your operating system:

[https://docs.docker.com/get-docker/](https://docs.docker.com/get-docker/)<br>

Or watch this video (Portuguese) to see how simple it is to test OcoMon without needing any installation:
[https://www.youtube.com/watch?v=Wtq-Z4M9w5M](https://www.youtube.com/watch?v=Wtq-Z4M9w5M)<br>



FIRST STEPS
-----------

ACCESS

    user: admin
    
    password: admin (**Don't forget to change this password as soon as you have access to the system!!**)

New users can be created in the menu [Admin::Users]
<br><br>


GENERAL SYSTEM SETTINGS
-----------------------

OcoMon has two areas for different system configurations:

- configuration file: /includes/config.inc.php
    - this file contains the database connection information, and default paths.

- The other system configurations are all accessible through the administration menu directly on the system interface. 
<br><br>



DOCUMENTATION:
-------------

All OcoMon documentation is available on the project website and on the YouTube channel:

+ Official site: [https://ocomonphp.sourceforge.io/](https://ocomonphp.sourceforge.io/)

+ Changelog: [https://ocomonphp.sourceforge.io/changelog-incremental/](https://ocomonphp.sourceforge.io/changelog-incremental/)

+ Twitter: [https://twitter.com/OcomonOficial](https://twitter.com/OcomonOficial)

+ Youtube Channel: [https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ](https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ)



### Contact:
+ E-mail: [ocomon.oficial@gmail.com](ocomon.oficial@gmail.com)



<br><br>I am convinced that OcoMon has the potential to be the tool that will be indispensable in the organization and management of your service area, freeing up your precious time for other accomplishments.

Have a good using!! :)

Flávio Ribeiro
[flaviorib@gmail.com](flaviorib@gmail)

