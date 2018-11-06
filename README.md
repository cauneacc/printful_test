# printful_test
1. Create the database printful_test
sql> create database printful_test;
2. Create the tables in the printful_test database
bash> mysql -u your_mysql_username -p printful_test < protected/scripts/db.sql
3. Add your database server connection settings in the application configuration file 
located at protected/config/db.php
4. Insert the test data using the script protected/scripts/insertTestData.php
php protected/scripts/insertTestData.php
5. Configure your web server to serve the php application. Only the index.php file 
and the static folder needs to be accesible from the browser.
