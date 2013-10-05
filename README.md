yii-EDbConnection
=================

Extends Yii's CDbConnection for MySQL Multi-Master Setups.

The EDbConnection component handles some kind of connection pooling in environments
with MySQL Multi-Master Setups.
This component/extension will poll MySQL Master server one after another until it finds
a functional server to create a connection.
Every unavailable server will be marked as dead and that information will be stored in
memcache. 
**For that reason you have to configure a cache component!**

## Installation

Just copy the EDbConnection.php file to your `protected/component` folder and add a
component to your `protected/config/main.php` like that (or modify an existing one):

       'db'=>array(
           'connectionString' => 'mysql:host=localhost;dbname=myDatabase;port=3306', // this is the original connectionString, can be removed
           'connectionArray' =>  array( // THIS IS IMPORTANT TO ADD!
               'mysql:host=mysql-server1.example.com;dbname=myDatabase;port=3306',
               'mysql:host=mysql-server2.example.com;dbname=myDatabase;port=3306'
           ),
           'emulatePrepare' => true,
           'username' => 'myUsername',
           'password' => 'myPassword',
           'charset' => 'utf8',
           'schemaCachingDuration'=>36000,
           'enableParamLogging' => true,
           'class'=>'EDbConnection'
       ),

## Credits

This piece of software was inspired by this extension/component:
http://www.yiiframework.com/extension/dbreadwritesplitting/
