<?php

/**
 * EDbConnection class file
 *
 * @author  5missions.de <5missions@posteo.de>
 * @license GPLv2
 * @version 1.0
 * @link   https://github.com/5missions/yii-EDbConnection
 */

/**
 * Extends Yii's CDbConnection and provides connection pooling in
 * MySQL Multi Master Setups
 *
 * @category Base
 * @package  system.db
 */
class EDbConnection extends CDbConnection
{

    /**
     * @var int $timeout set default 3 seconds connection timeout
     */

    public $timeout =3 ;

    /**
     * Default, if the connected server caused error, then mark this server as
     * dead for 10 minutes. After 10 minutes, mark dead cache will automatically expire.
     * @todo make this configurable at main.php
     *
     * @var int $markDeadSeconds
     */

    public $markDeadSeconds = 600;

    /**
     * use cache as global flags storage
     * @var string $cacheID
     */
    public $cacheID='cache';

    /**
    * @var array $connectionArray.DSN database connection config array.
    * The main server must be set as connection string and at first position of
    * the connectionArray
    * @example
    * 	'db'=>array(
    *           'connectionString'=>'mysql://...',
    * 		        'connectionArray'=>array() {
    *               'mysql://...',
    *               'mysql://...''
    *           )
    * 	)
    **/
    public $connectionArray = array();

    /**
     * Overwrites CDbConnection::setActive
     * @param string $value Value
     * 
     * @return void
     */
    public function setActive($value)
    {
        Yii::trace('Using custom setActive()','system.db.EDbConnection');
        if ($value != $this->getActive()) {
            if ($value) {

                // if main.php was not altered we will catch that
                if (empty($this->connectionArray)
                        && isset($this->connectionString)
                        && !empty($this->connectionString)
                ) {
                    Yii::trace("Configuration was not altered!",'system.db.EDbConnection');
                    $this->connectionArray[] = $this->connectionString;
                }

                foreach ($this->connectionArray as $connectionString) {

                    if (!$this->_isDeadServer($connectionString)) {
                        Yii::trace(
                                "Try to connect to: $connectionString",
                                'system.db.NDbConnection'
                        );
                        //PDO::ATTR_TIMEOUT must set before pdo instance create
                        $this->connectionString = $connectionString;
                        try {
                            $this->setAttribute(PDO::ATTR_TIMEOUT,$this->timeout);
                            $this->open();
                            // If we have been successful stop trying
                            Yii::trace(
                                    "Connection successfully established: $connectionString",
                                    'system.db.EDbConnection'
                            );
                            break;
                        } catch (Exception $e) {
                            Yii::trace(
                                    "Connection failed: $this->connectionString",
                                    'system.db.EDbConnection'
                            );
                            $this->_markDeadServer($this->connectionString);
                            Yii::log($e->getMessage(),CLogger::LEVEL_ERROR,'exception.EDbException');
                        }
                    }

                }

            } else {
                $this->close();
            }
        }

        // If none of the MySQL server have responded
        if (!$this->getActive()) {
            $this->_deleteAllServerFromCache($this->connectionArray);
            echo "There are no MySQL Server available!\n<br>";

            throw new CDbException('CDbConnection failed to open the DB connection.',1,'none');
        }

    }

    /**
     * Detect is this server config already marked as dead for a period time in
     * cache.
     * 
     * @param  string $connectionString The PDO Connection String
     * @return bool
     */
    private function _isDeadServer($connectionString)
    {
        $cache = Yii::app()->{$this->cacheID};

        if ($cache && $cache->get('DeadServer::'.$connectionString) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Mark this server config as dead.
     * @param  string $connectionString The PDO Connection String
     * @return void
     */
    private function _markDeadServer($connectionString)
    {
        $cache = Yii::app()->{$this->cacheID};
        if ($cache) {
            $cache->set('DeadServer::'.$connectionString, 1, $this->markDeadSeconds);
        }
    }

    /**
     * Delete all server from cache in case that no server is responding
     * to force checks after server came back online
     * @param  array $connectionsStrings Array with PDO Connection Strings
     * @return void
     */
    private function _deleteAllServerFromCache($connectionStrings)
    {
        $cache = Yii::app()->{$this->cacheID};
        if ($cache) {
            foreach ($connectionStrings as $connectionString) {
                $cache->delete('DeadServer::'.$connectionString);
            }
        }
    }
}
